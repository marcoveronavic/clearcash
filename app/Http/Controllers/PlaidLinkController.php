<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\BankConnection;
use App\Models\PlaidTransaction;

class PlaidLinkController extends Controller
{
    private string $baseUrl;
    private string $clientId;
    private string $secret;

    public function __construct()
    {
        $env = env('PLAID_ENV', 'sandbox');
        $this->baseUrl = match ($env) {
            'production'  => 'https://production.plaid.com',
            'development' => 'https://development.plaid.com',
            default       => 'https://sandbox.plaid.com',
        };

        $this->clientId = (string) env('PLAID_CLIENT_ID', '');
        $this->secret   = (string) env('PLAID_SECRET', '');
    }

    /**
     * POST /api/plaid/link-token
     */
    public function createLinkToken(Request $request)
    {
        // Normalizza "products"
        $products = $request->input('products', ['transactions']);
        if (is_string($products)) {
            $products = array_values(array_filter(array_map('trim', explode(',', $products))));
            if (empty($products)) $products = ['transactions'];
        }

        // Normalizza "country_codes"
        $countryCodes = $request->input('country_codes', ['US', 'GB', 'IT', 'FR', 'DE']);
        if (is_string($countryCodes)) {
            $countryCodes = array_values(array_filter(array_map('trim', explode(',', $countryCodes))));
            if (empty($countryCodes)) $countryCodes = ['US', 'GB', 'IT'];
        }

        $language        = $request->input('language', 'en');
        $externalUserId  = (string)($request->user()->id ?? $request->input('user_id') ?? ('guest-' . uniqid()));
        $clientName      = config('app.name', 'ClearCash');

        $payload = [
            'client_id'     => $this->clientId,
            'secret'        => $this->secret,
            'client_name'   => $clientName,
            'language'      => $language,
            'country_codes' => $countryCodes,
            'user'          => ['client_user_id' => $externalUserId],
            'products'      => $products,
        ];

        if ($redirectUri = (string) env('PLAID_REDIRECT_URI', '')) {
            $payload['redirect_uri'] = $redirectUri;
        }

        $resp = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/link/token/create', $payload);

        if (!$resp->successful()) {
            return response()->json([
                'error'  => 'plaid_request_failed',
                'status' => $resp->status(),
                'json'   => $resp->json(),
                'body'   => $resp->body(),
            ], $resp->status() ?: 500);
        }

        return response()->json($resp->json(), 200);
    }

    /**
     * POST /api/plaid/exchange
     * body: { "public_token": "...", "institution_id": "...", "institution_name": "..." }
     */
    public function exchange(Request $request)
    {
        $request->validate([
            'public_token'     => 'required|string|min:10',
            'institution_id'   => 'nullable|string|max:64',
            'institution_name' => 'nullable|string|max:191',
        ]);

        // Idempotenza sul public_token
        $pt  = $request->string('public_token')->toString();
        $key = 'plaid:pubtok:' . hash('sha256', $pt);
        if (Cache::has($key)) {
            return response()->json(['status' => 'already-exchanged-or-inflight'], 200);
        }
        Cache::put($key, true, now()->addMinutes(10));

        $payload = [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'public_token' => $pt,
        ];

        $resp = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/item/public_token/exchange', $payload);

        if (!$resp->successful()) {
            return response()->json([
                'error'  => 'plaid_request_failed',
                'status' => $resp->status(),
                'json'   => $resp->json(),
                'body'   => $resp->body(),
            ], $resp->status() ?: 500);
        }

        $data        = $resp->json();
        $accessToken = $data['access_token'] ?? null;
        $itemId      = $data['item_id'] ?? null;

        if (!$accessToken || !$itemId) {
            return response()->json([
                'error'   => 'missing_fields_from_plaid',
                'details' => $data,
            ], 500);
        }

        $userId = Auth::id();

        $connection = BankConnection::updateOrCreate(
            ['item_id' => $itemId],
            [
                'user_id'          => $userId,
                'access_token'     => $accessToken,
                'institution_id'   => $request->input('institution_id'),
                'institution_name' => $request->input('institution_name'),
                'raw'              => $data,
            ]
        );

        return response()->json([
            'saved'               => true,
            'bank_connection_id'  => $connection->id,
            'item_id'             => $itemId,
        ], 200);
    }

    /**
     * GET /api/plaid/accounts/{id}
     */
    public function accounts($id, Request $request)
    {
        $connection = BankConnection::findOrFail((int) $id);

        $payload = [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'access_token' => $connection->access_token,
        ];

        $resp = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/accounts/get', $payload);

        if (!$resp->successful()) {
            return response()->json([
                'error'  => 'plaid_request_failed',
                'status' => $resp->status(),
                'json'   => $resp->json(),
                'body'   => $resp->body(),
            ], $resp->status() ?: 500);
        }

        $json = $resp->json();

        return response()->json([
            'bank_connection_id' => $connection->id,
            'accounts'           => $json['accounts'] ?? [],
            'request_id'         => $json['request_id'] ?? null,
        ], 200);
    }

    /**
     * POST /api/plaid/transactions/sync
     * Body:
     *   { "bank_connection_id": 3, "cursor": "opz.", "count": 100 }
     */
    public function transactionsSync(Request $request)
    {
        $request->validate([
            'bank_connection_id' => 'required|integer|min:1',
            'cursor'             => 'nullable|string',
            'count'              => 'nullable|integer|min:1|max:500',
        ]);

        $connection = BankConnection::findOrFail((int) $request->input('bank_connection_id'));

        $payload = [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'access_token' => $connection->access_token,
            'count'        => $request->input('count', 100),
        ];

        if ($cursor = $request->input('cursor')) {
            $payload['cursor'] = $cursor;
        }

        $resp = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/transactions/sync', $payload);

        if (!$resp->successful()) {
            return response()->json([
                'error'  => 'plaid_request_failed',
                'status' => $resp->status(),
                'json'   => $resp->json(),
                'body'   => $resp->body(),
            ], $resp->status() ?: 500);
        }

        $json = $resp->json();

        return response()->json([
            'bank_connection_id' => $connection->id,
            'added'              => $json['added'] ?? [],
            'modified'           => $json['modified'] ?? [],
            'removed'            => $json['removed'] ?? [],
            'next_cursor'        => $json['next_cursor'] ?? null,
            'has_more'           => $json['has_more'] ?? false,
            'request_id'         => $json['request_id'] ?? null,
        ], 200);
    }

    /**
     * POST /api/plaid/transactions/sync-store
     * Body:
     *   { "bank_connection_id": 3, "cursor": "opz.", "max_loops": 10, "count": 100 }
     * Fa loop su /transactions/sync finché has_more = false o supera max_loops.
     * Salva/aggiorna idempotente su transaction_id. Marca removed con is_removed=true.
     * Salva transactions_cursor e last_synced_at su bank_connections.
     */
    public function transactionsSyncStore(Request $request)
    {
        $request->validate([
            'bank_connection_id' => 'required|integer|min:1',
            'cursor'             => 'nullable|string',
            'count'              => 'nullable|integer|min:1|max:500',
            'max_loops'          => 'nullable|integer|min:1|max:100',
        ]);

        $connection = BankConnection::findOrFail((int) $request->input('bank_connection_id'));

        // Usa cursor passato o quello salvato in tabella (transactions_cursor)
        $cursor   = $request->input('cursor', $connection->transactions_cursor);
        $count    = (int) $request->input('count', 100);
        $maxLoops = (int) $request->input('max_loops', 10);

        $totalAdded = 0;
        $totalModified = 0;
        $totalRemoved = 0;

        for ($i = 0; $i < $maxLoops; $i++) {
            $payload = [
                'client_id'    => $this->clientId,
                'secret'       => $this->secret,
                'access_token' => $connection->access_token,
                'count'        => $count,
            ];
            if ($cursor) $payload['cursor'] = $cursor;

            $resp = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . '/transactions/sync', $payload);

            if (!$resp->successful()) {
                return response()->json([
                    'error'  => 'plaid_request_failed',
                    'status' => $resp->status(),
                    'json'   => $resp->json(),
                    'body'   => $resp->body(),
                ], $resp->status() ?: 500);
            }

            $json = $resp->json();
            $added    = $json['added']    ?? [];
            $modified = $json['modified'] ?? [];
            $removed  = $json['removed']  ?? [];
            $cursor   = $json['next_cursor'] ?? $cursor;
            $hasMore  = (bool)($json['has_more'] ?? false);

            // Upsert idempotente (transaction_id unico)
            DB::transaction(function () use ($connection, $added, $modified, $removed, &$totalAdded, &$totalModified, &$totalRemoved) {
                // added
                foreach ($added as $t) {
                    $this->upsertPlaidTx($connection->id, $t, false);
                    $totalAdded++;
                }
                // modified
                foreach ($modified as $t) {
                    $this->upsertPlaidTx($connection->id, $t, false);
                    $totalModified++;
                }
                // removed -> marcare is_removed
                foreach ($removed as $t) {
                    $txId = $t['transaction_id'] ?? null;
                    if ($txId) {
                        PlaidTransaction::where('transaction_id', $txId)->update(['is_removed' => true]);
                        $totalRemoved++;
                    }
                }
            });

            if (!$hasMore) break;
        }

        // Salva cursor e timestamp sulla connessione
        $connection->transactions_cursor = $cursor;
        $connection->last_synced_at      = now();
        $connection->save();

        return response()->json([
            'bank_connection_id' => $connection->id,
            'next_cursor'        => $cursor,
            'counters'           => [
                'added'    => $totalAdded,
                'modified' => $totalModified,
                'removed'  => $totalRemoved,
                'loops'    => min($i + 1, $maxLoops),
            ],
            'saved_to_connection' => true,
        ], 200);
    }

    /**
     * GET /api/plaid/transactions/export/{id}
     * Query params opzionali:
     *   from=YYYY-MM-DD, to=YYYY-MM-DD, merchant=string, pending=0|1, min=float, max=float
     */
    public function exportTransactionsCsv(Request $request, int $id)
    {
        $query = PlaidTransaction::where('bank_connection_id', (int) $id);

        if ($from = $request->query('from')) {
            $query->where('date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('date', '<=', $to);
        }
        if ($merchant = $request->query('merchant')) {
            $query->where(function ($q) use ($merchant) {
                $q->where('merchant_name', 'like', '%' . $merchant . '%')
                    ->orWhere('name', 'like', '%' . $merchant . '%');
            });
        }
        if (($pendingParam = $request->query('pending')) !== null && $pendingParam !== '') {
            $pendingBool = in_array(strtolower((string) $pendingParam), ['1','true','yes'], true);
            $query->where('pending', $pendingBool);
        }
        if ($min = $request->query('min')) {
            $query->where('amount', '>=', (float) $min);
        }
        if ($max = $request->query('max')) {
            $query->where('amount', '<=', (float) $max);
        }

        $query->orderByDesc('date')->orderBy('transaction_id');

        $filename = "plaid_transactions_{$id}_" . now()->format('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM per Excel
            fwrite($out, "\xEF\xBB\xBF");

            // Header CSV
            fputcsv($out, [
                'date','name','merchant_name','amount','iso_currency_code',
                'pending','transaction_id','account_id'
            ]);

            // Stream a chunk per non caricare tutto in RAM
            $query->chunk(1000, function ($chunk) use ($out) {
                foreach ($chunk as $t) {
                    fputcsv($out, [
                        is_object($t->date) ? $t->date->format('Y-m-d') : $t->date,
                        $t->name,
                        $t->merchant_name,
                        $t->amount,
                        $t->iso_currency_code,
                        $t->pending ? 1 : 0,
                        $t->transaction_id,
                        $t->account_id,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Upsert di UNA transazione plaid in plaid_transactions.
     * $markRemoved=true forza is_removed=1 (non usato ora sugli added/modified).
     */
    protected function upsertPlaidTx(int $bankConnectionId, array $t, bool $markRemoved = false): void
    {
        $base = [
            'bank_connection_id'        => $bankConnectionId,
            'account_id'                => $t['account_id']           ?? null,
            'transaction_id'            => $t['transaction_id']       ?? null,
            'pending_transaction_id'    => $t['pending_transaction_id'] ?? null,
            'amount'                    => $t['amount']               ?? 0,
            'iso_currency_code'         => $t['iso_currency_code']    ?? null,
            'unofficial_currency_code'  => $t['unofficial_currency_code'] ?? null,
            'date'                      => $t['date']                 ?? null,
            'authorized_date'           => $t['authorized_date']      ?? null,
            'datetime'                  => $t['datetime']             ?? null,
            'authorized_datetime'       => $t['authorized_datetime']  ?? null,
            'name'                      => $t['name']                 ?? null,
            'merchant_name'             => $t['merchant_name']        ?? null,
            'merchant_entity_id'        => $t['merchant_entity_id']   ?? null,
            'payment_channel'           => $t['payment_channel']      ?? null,
            'transaction_type'          => $t['transaction_type']     ?? null,
            'transaction_code'          => $t['transaction_code']     ?? null,
            'check_number'              => $t['check_number']         ?? null,
            'pending'                   => (bool)($t['pending']       ?? false),
            'logo_url'                  => $t['logo_url']             ?? null,
            'website'                   => $t['website']              ?? null,
            'category'                  => $t['category']             ?? null,
            'counterparties'            => $t['counterparties']       ?? null,
            'personal_finance_category' => $t['personal_finance_category'] ?? null,
            'location'                  => $t['location']             ?? null,
            'raw'                       => $t, // payload completo per audit
            'is_removed'                => $markRemoved ? true : false,
        ];

        // idempotenza su transaction_id
        PlaidTransaction::updateOrCreate(
            ['transaction_id' => $base['transaction_id']],
            $base
        );
    }

    // Alias compatibilità
    public function exchangePublicToken(Request $request)
    {
        return $this->exchange($request);
    }
}
