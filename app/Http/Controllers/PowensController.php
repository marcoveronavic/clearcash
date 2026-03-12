<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BudgetCategory;
use App\Models\PowensConnection;
use App\Services\PowensService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PowensController extends Controller
{
    public function __construct(private PowensService $powens)
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Reindirizza l'utente alla Webview Powens per collegare una banca.
     */
    public function connect(Request $request)
    {
        $user = Auth::user();

        if ($request->has('from_setup')) {
            session(['powens_return_to' => 'setup']);
        }

        if (!$user->powens_user_id) {
            $result = $this->powens->createUser();

            $user->update([
                'powens_user_id'    => $result['user_id'],
                'powens_user_token' => $result['token'],
            ]);
        }

        $redirectUri = route('powens.callback');
        $connectUrl  = $this->powens->getConnectUrl($user->powens_user_token, $redirectUri);

        return redirect()->away($connectUrl);
    }

    /**
     * Callback dopo che l'utente ha collegato la banca nella Webview.
     */
    public function callback(Request $request)
    {
        $user = Auth::user();

        Log::info('Powens callback hit', ['user_id' => $user->id, 'params' => $request->all()]);

        $code = $request->get('code');
        if ($code) {
            try {
                $exchange = $this->powens->exchangeCode($code);
                Log::info('Powens code exchange', ['result' => $exchange]);

                if (!empty($exchange['token'])) {
                    $user->update([
                        'powens_user_id'    => $exchange['user_id'] ?? $user->powens_user_id,
                        'powens_user_token' => $exchange['token'],
                    ]);
                    $user->refresh();
                }
            } catch (\Exception $e) {
                Log::error('Powens code exchange failed', ['error' => $e->getMessage()]);
            }
        }

        if (!$user->powens_user_token) {
            Log::warning('No powens_user_token for user', ['user_id' => $user->id]);
            return redirect()->route('bank-accounts.index')
                ->with('error', 'Nessun account Powens collegato.');
        }

        try {
            $connections = $this->powens->getConnections($user->powens_user_token);
            Log::info('Powens connections', ['count' => count($connections), 'data' => $connections]);

            foreach ($connections as $conn) {
                PowensConnection::updateOrCreate(
                    ['powens_connection_id' => $conn['id']],
                    [
                        'user_id'              => $user->id,
                        'powens_connector_id'  => $conn['id_connector'] ?? null,
                        'institution_name'     => $conn['connector']['name'] ?? null,
                        'state'                => $conn['state'] ?? null,
                        'error_message'        => $conn['error_message'] ?? null,
                        'last_sync_at'         => $conn['last_update'] ?? null,
                        'raw'                  => json_encode($conn),
                    ]
                );
            }

            $accounts = $this->powens->getAccounts($user->powens_user_token);
            Log::info('Powens accounts', ['count' => count($accounts), 'data' => $accounts]);

            foreach ($accounts as $acc) {
                BankAccount::updateOrCreate(
                    [
                        'user_id'           => $user->id,
                        'powens_account_id' => $acc['id'],
                    ],
                    [
                        'account_name'          => $acc['name'] ?? 'Conto',
                        'account_type'          => $acc['type'] ?? 'checking',
                        'starting_balance'      => $acc['balance'] ?? 0,
                        'powens_connection_id'  => $acc['id_connection'] ?? null,
                        'iban'                  => $acc['iban'] ?? null,
                        'currency'              => $acc['currency']['id'] ?? 'EUR',
                        'institution_name'      => $this->getInstitutionName($connections, $acc['id_connection'] ?? null),
                    ]
                );
            }

            $returnTo = session()->pull('powens_return_to');

            if ($returnTo === 'setup') {
                return redirect()->route('account-setup.step-five')
                    ->with('success', 'Conto bancario collegato con successo!');
            }

            return redirect()->route('bank-accounts.index')
                ->with('success', 'Conto bancario collegato con successo!');

        } catch (\Exception $e) {
            Log::error('Powens callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('bank-accounts.index')
                ->with('error', 'Errore durante il collegamento: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizza manualmente transazioni da Powens.
     */
    public function syncTransactions()
    {
        $user = Auth::user();

        if (!$user->powens_user_token) {
            return back()->with('error', 'Nessun account Powens collegato.');
        }

        try {
            $transactions = $this->powens->getTransactions($user->powens_user_token);
            $imported = 0;

            foreach ($transactions as $tx) {
                $bankAccount = BankAccount::where('user_id', $user->id)
                    ->where('powens_account_id', $tx['id_account'])
                    ->first();

                if (!$bankAccount) continue;

                $exists = \App\Models\Transaction::where('powens_transaction_id', $tx['id'])->exists();
                if ($exists) continue;

                $value = $tx['value'] ?? 0;
                $isExpense = $value < 0;
                $categoryId = $this->mapCategory($tx);
                $categoryName = $this->getCategoryName($categoryId);

                \App\Models\Transaction::create([
                    'powens_transaction_id' => $tx['id'],
                    'is_from_powens'        => true,
                    'name'                  => $tx['wording'] ?? $tx['original_wording'] ?? 'Transazione',
                    'date'                  => $tx['date'] ?? now()->toDateString(),
                    'description'           => $tx['original_wording'] ?? null,
                    'amount'                => abs($value),
                    'transaction_type'      => $isExpense ? 'expense' : 'income',
                    'category_name'         => $categoryName,
                    'bank_account_id'       => $bankAccount->id,
                    'user_id'               => $user->id,
                    'category_id'           => $categoryId,
                ]);

                $imported++;
            }

            return back()->with('success', "{$imported} transazioni importate.");

        } catch (\Exception $e) {
            Log::error('Powens sync error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Errore durante la sincronizzazione.');
        }
    }

    private function getInstitutionName(array $connections, ?int $connectionId): ?string
    {
        foreach ($connections as $conn) {
            if (($conn['id'] ?? null) === $connectionId) {
                return $conn['connector']['name'] ?? null;
            }
        }
        return null;
    }

    private function mapCategory(array $tx): ?int
    {
        $userId = Auth::id();

        // Mapping Powens category ID -> nome categoria italiana
        $powensMap = [
            // Stipendio/Reddito
            9 => 'stipendio',
            89 => 'stipendio',

            // Casa
            5 => 'affitto',
            6 => 'mutuo',
            7 => 'bollette',
            71 => 'bollette',
            72 => 'bollette',
            73 => 'bollette',

            // Spesa
            10 => 'spesa_alimentare',
            101 => 'spesa_alimentare',

            // Ristoranti
            11 => 'ristoranti',
            111 => 'ristoranti',

            // Trasporti
            12 => 'trasporti',
            121 => 'carburante',
            122 => 'trasporti',

            // Shopping
            13 => 'abbigliamento',
            131 => 'abbigliamento',

            // Salute
            14 => 'salute',
            141 => 'farmacia',

            // Assicurazioni
            15 => 'assicurazioni',

            // Telecomunicazioni
            16 => 'telefono_internet',

            // Abbonamenti/Servizi
            17 => 'abbonamenti',

            // Svago
            18 => 'intrattenimento',
            181 => 'intrattenimento',

            // Viaggi
            19 => 'viaggi',

            // Istruzione
            20 => 'istruzione',

            // Tasse
            21 => 'tasse',

            // Risparmi/Investimenti
            22 => 'risparmi',
            23 => 'investimenti',

            // Figli
            24 => 'figli',

            // Sport
            25 => 'sport_fitness',

            // Animali
            26 => 'animali_domestici',

            // Regali
            27 => 'regali',
            28 => 'donazioni',
        ];

        $powensCatId = $tx['id_category'] ?? null;
        $targetName = $powensMap[$powensCatId] ?? 'non_categorizzato';

        $category = BudgetCategory::where('user_id', $userId)
            ->where('name', $targetName)
            ->first();

        if ($category) {
            return (int) $category->id;
        }

        // Fallback: non_categorizzato
        $fallback = BudgetCategory::where('user_id', $userId)
            ->where('name', 'non_categorizzato')
            ->first();

        return $fallback ? (int) $fallback->id : null;
    }

    private function getCategoryName(?int $categoryId): string
    {
        if (!$categoryId) {
            return 'non_categorizzato';
        }

        $category = BudgetCategory::find($categoryId);

        return $category ? $category->name : 'non_categorizzato';
    }
}
