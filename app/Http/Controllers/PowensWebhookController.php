<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BudgetCategory;
use App\Models\PowensConnection;
use App\Models\User;
use App\Services\PowensService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PowensWebhookController extends Controller
{
    public function handle(Request $request, PowensService $powens)
    {
        $payload = $request->all();
        Log::info('Powens webhook received', ['payload' => $payload]);

        // Powens manda diversi tipi di evento
        // CONNECTION_SYNCED è il più importante: significa che nuovi dati sono disponibili
        $userId = $payload['id_user'] ?? null;
        $connectionId = $payload['id_connection'] ?? null;

        if (!$userId) {
            Log::warning('Powens webhook: no id_user');
            return response()->json(['status' => 'ignored']);
        }

        // Trova l'utente ClearCash tramite powens_user_id
        $user = User::where('powens_user_id', $userId)->first();

        if (!$user || !$user->powens_user_token) {
            Log::warning('Powens webhook: user not found', ['powens_user_id' => $userId]);
            return response()->json(['status' => 'user_not_found']);
        }

        try {
            // Aggiorna connessioni
            $connections = $powens->getConnections($user->powens_user_token);

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

            // Aggiorna conti
            $accounts = $powens->getAccounts($user->powens_user_token);

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
                    ]
                );
            }

            // Importa nuove transazioni
            $transactions = $powens->getTransactions($user->powens_user_token);
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
                $categoryId = $this->mapCategory($user->id, $tx);
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

            Log::info('Powens webhook sync complete', [
                'user_id' => $user->id,
                'imported' => $imported,
            ]);

            return response()->json(['status' => 'ok', 'imported' => $imported]);

        } catch (\Exception $e) {
            Log::error('Powens webhook error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    private function mapCategory(int $userId, array $tx): ?int
    {
        $powensMap = [
            9 => 'stipendio', 89 => 'stipendio',
            5 => 'affitto', 6 => 'mutuo',
            7 => 'bollette', 71 => 'bollette', 72 => 'bollette', 73 => 'bollette',
            10 => 'spesa_alimentare', 101 => 'spesa_alimentare',
            11 => 'ristoranti', 111 => 'ristoranti',
            12 => 'trasporti', 121 => 'carburante', 122 => 'trasporti',
            13 => 'abbigliamento', 131 => 'abbigliamento',
            14 => 'salute', 141 => 'farmacia',
            15 => 'assicurazioni', 16 => 'telefono_internet',
            17 => 'abbonamenti', 18 => 'intrattenimento', 181 => 'intrattenimento',
            19 => 'viaggi', 20 => 'istruzione', 21 => 'tasse',
            22 => 'risparmi', 23 => 'investimenti',
            24 => 'figli', 25 => 'sport_fitness',
            26 => 'animali_domestici', 27 => 'regali', 28 => 'donazioni',
        ];

        $powensCatId = $tx['id_category'] ?? null;
        $targetName = $powensMap[$powensCatId] ?? 'non_categorizzato';

        $category = BudgetCategory::where('user_id', $userId)
            ->where('name', $targetName)
            ->first();

        if ($category) {
            return (int) $category->id;
        }

        $fallback = BudgetCategory::where('user_id', $userId)
            ->where('name', 'non_categorizzato')
            ->first();

        return $fallback ? (int) $fallback->id : null;
    }

    private function getCategoryName(?int $categoryId): string
    {
        if (!$categoryId) return 'non_categorizzato';

        $category = BudgetCategory::find($categoryId);
        return $category ? $category->name : 'non_categorizzato';
    }
}
