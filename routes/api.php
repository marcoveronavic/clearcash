<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App; // check ambiente
use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Http\Controllers\PlaidLinkController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Queste rotte sono prefissate con /api e NON richiedono CSRF.
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// (Opzionale) Health check
Route::get('health', fn () => response()->json(['ok' => true]));

/*
|--------------------------------------------------------------------------
| Plaid
|--------------------------------------------------------------------------
| - POST /api/plaid/link-token               : genera link_token per Plaid Link
| - POST /api/plaid/exchange                 : scambia public_token -> access_token (idempotente)
| - POST /api/plaid/exchange-public          : alias compatibilità
| - POST /api/plaid/transactions/sync        : sync transazioni (delta/snapshot)
| - POST /api/plaid/transactions/sync-store  : sync + salvataggio DB (loop finché has_more=false)
| - GET  /api/plaid/transactions/export/{id} : export CSV con filtri via query string
| - GET  /api/plaid/accounts/{id}            : accounts per BankConnection {id}
| - GET  /api/plaid/transactions/count/{id}  : (DEV) conteggio transazioni salvate
| - GET  /api/plaid/transactions/sample/{id} : (DEV) sample transazioni salvate
|--------------------------------------------------------------------------
*/

if (App::environment('local')) {
    // In sviluppo: throttle disabilitato per evitare 429 del gruppo 'api'
    Route::post('plaid/link-token', [PlaidLinkController::class, 'createLinkToken'])
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.link_token.create');

    Route::post('plaid/exchange', [PlaidLinkController::class, 'exchange'])
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.exchange');

    Route::post('plaid/exchange-public', [PlaidLinkController::class, 'exchangePublicToken'])
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.exchangePublic');

    Route::post('plaid/transactions/sync', [PlaidLinkController::class, 'transactionsSync'])
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.transactions.sync');

    Route::post('plaid/transactions/sync-store', [PlaidLinkController::class, 'transactionsSyncStore'])
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.transactions.sync_store');

    // Export CSV (dev: senza throttle)
    Route::get('plaid/transactions/export/{id}', [PlaidLinkController::class, 'exportTransactionsCsv'])
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.transactions.export');

    // --- DEBUG DEV-ONLY: conteggio e sample transazioni salvate (senza throttle) ---
    Route::get('plaid/transactions/count/{id}', function (int $id) {
        $count = \App\Models\PlaidTransaction::where('bank_connection_id', $id)->count();
        $conn  = \App\Models\BankConnection::find($id);

        return response()->json([
            'bank_connection_id'  => $id,
            'count'               => $count,
            'last_synced_at'      => optional($conn)->last_synced_at,
            'transactions_cursor' => optional($conn)->transactions_cursor,
        ]);
    })
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.transactions.count');

    Route::get('plaid/transactions/sample/{id}', function (int $id) {
        $rows = \App\Models\PlaidTransaction::where('bank_connection_id', $id)
            ->orderByDesc('date')
            ->limit(10)
            ->get(['date','name','merchant_name','amount','iso_currency_code','pending','transaction_id']);

        return response()->json([
            'bank_connection_id' => $id,
            'sample'             => $rows,
        ]);
    })
        ->withoutMiddleware([ThrottleRequests::class])
        ->name('plaid.transactions.sample');

} else {
    // In altri ambienti: throttle permissivo (regola a piacere)
    Route::post('plaid/link-token', [PlaidLinkController::class, 'createLinkToken'])
        ->middleware('throttle:600,1')
        ->name('plaid.link_token.create');

    Route::post('plaid/exchange', [PlaidLinkController::class, 'exchange'])
        ->middleware('throttle:600,1')
        ->name('plaid.exchange');

    Route::post('plaid/exchange-public', [PlaidLinkController::class, 'exchangePublicToken'])
        ->middleware('throttle:600,1')
        ->name('plaid.exchangePublic');

    Route::post('plaid/transactions/sync', [PlaidLinkController::class, 'transactionsSync'])
        ->middleware('throttle:600,1')
        ->name('plaid.transactions.sync');

    Route::post('plaid/transactions/sync-store', [PlaidLinkController::class, 'transactionsSyncStore'])
        ->middleware('throttle:600,1')
        ->name('plaid.transactions.sync_store');

    // Export CSV (prod: con throttle)
    Route::get('plaid/transactions/export/{id}', [PlaidLinkController::class, 'exportTransactionsCsv'])
        ->middleware('throttle:600,1')
        ->name('plaid.transactions.export');
}

// Accounts per una BankConnection salvata (usa access_token da DB)
Route::get('plaid/accounts/{id}', [PlaidLinkController::class, 'accounts'])
    ->name('plaid.accounts.show');
