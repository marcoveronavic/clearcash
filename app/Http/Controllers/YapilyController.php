<?php

use App\Http\Controllers\AccountSetupController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminImageUploadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customer\CustomerBankAccountController;
use App\Http\Controllers\Customer\CustomerBudgetController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerMyAccountController;
use App\Http\Controllers\Customer\CustomerRecurringPayments;
use App\Http\Controllers\Customer\CustomerTransactionController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\PlaidController;
use App\Http\Controllers\YapilyController; // <<< AGGIUNTO
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('root');

Route::redirect('/home', '/dashboard', 301)->name('home');

/*
|--------------------------------------------------------------------------
| Plaid pages + API endpoints
|--------------------------------------------------------------------------
*/

Route::get('/plaid-link', function () {
    return view('plaid-link'); // facoltativa (resources/views/plaid-link.blade.php)
})->name('plaid.link.page');

Route::middleware(['auth', 'verified'])->group(function () {
    // (facoltativa) demo UI se esiste resources/views/plaid/demo.blade.php
    Route::view('/plaid-link-demo', 'plaid.demo')->name('plaid.demo');

    // API Plaid
    Route::post('/plaid/link-token', [PlaidController::class, 'createLinkToken'])->name('plaid.link-token');
    Route::post('/plaid/exchange',    [PlaidController::class, 'exchangePublicToken'])->name('plaid.exchange');

    // CALLBACK OAUTH → view che riapre Link con receivedRedirectUri
    Route::view('/plaid/oauth-return', 'plaid.oauth-return')->name('plaid.oauth.return');
});

/*
|--------------------------------------------------------------------------
| YAPILY (Open Banking) - rotte minime
|--------------------------------------------------------------------------
|
| - callback pubblico: Yapily redireziona l'utente qui dopo l'SCA della banca
| - le altre rotte sono dietro auth/verified
|
*/

// Callback pubblico (ritorno consenso da Yapily/banca)
Route::get('/yapily/callback', [YapilyController::class, 'callback'])->name('yapily.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('yapily')->name('yapily.')->group(function () {
        // Elenco istituti (per UI di scelta banca)
        Route::get('/institutions', [YapilyController::class, 'institutions'])->name('institutions');

        // Avvio autorizzazione per una banca (usa {institutionId} es. modelo-sandbox / intesa-smp / barclays)
        Route::get('/start/{institutionId}', [YapilyController::class, 'start'])->name('start');

        // Lettura conti (richiede token di consenso in sessione ottenuto nel callback)
        Route::get('/accounts', [YapilyController::class, 'accounts'])->name('accounts');

        // Lettura transazioni per account (passa l'ID dell'account restituito da /accounts)
        Route::get('/transactions/{accountId}', [YapilyController::class, 'transactions'])->name('transactions');
    });
});

/*
|--------------------------------------------------------------------------
| Account Setup
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('account-setup-step-one',  [AccountSetupController::class, 'index'])->name('account-setup.step-one');
    Route::post('account-setup-step-one-store', [AccountSetupController::class, 'indexStore'])->name('account-setup.step-one-store');

    Route::get('account-setup-step-two',  [AccountSetupController::class, 'stepTwoShow'])->name('account-setup.step-two');
    Route::post('account-setup-step-two-store', [AccountSetupController::class,'stepTwoStore'])->name('account-setup-step-two-store');

    Route::get('account-setup-step-three', [AccountSetupController::class, 'stepThreeShow'])->name('account-setup.step-three');
    Route::post('account-setup-step-three-store', [AccountSetupController::class, 'stepThreeStore'])->name('account-setup.step-three-store');

    Route::get('account-setup-step-four', [AccountSetupController::class, 'stepFourShow'])->name('account-setup.step-four');
    Route::post('account-setup-step-four-store', [AccountSetupController::class, 'stepFourStore'])->name('account-setup.step-four-store');

    Route::get('account-setup-step-five', [AccountSetupController::class, 'stepFiveShow'])->name('account-setup.step-five');
    Route::post('account-setup-step-five-store', [AccountSetupController::class, 'stepFiveStore'])->name('account-setup.step-five-store');

    Route::get('account-setup-step-six',  [AccountSetupController::class, 'stepSixShow'])->name('account-setup.step-six');

    Route::get('account-setup-step-six-investments', [AccountSetupController::class, 'stepSixInvestmentsShow'])->name('account-setup.step-six-investments');

    // POST esistente (nome con trattini) — lasciato intatto
    Route::post('account-setup-step-six-investments-store', [AccountSetupController::class, 'stepSixInvestmentsStore'])
        ->name('account-setup-step-six-investments-store');

    // ✅ Alias POST con NOME A PUNTINI (quello che usa il Blade): nessun impatto sul resto
    //     Riutilizza lo stesso metodo ma su URI POST distinto (o sullo stesso con metodo diverso dal GET).
    Route::post('account-setup-step-six-investments', [AccountSetupController::class, 'stepSixInvestmentsStore'])
        ->name('account-setup.step-six-investments-store');

    Route::get('account-setup-step-seven', [AccountSetupController::class, 'stepSevenShow'])->name('account-setup.step-seven');
});

/*
|--------------------------------------------------------------------------
| Client / Customer
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super admin|customer', 'verified'])->group(function () {

    Route::post('reset-account', [CustomerMyAccountController::class, 'resetAccount'])->name('reset-account');

    Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

    // Bank Accounts
    Route::resource('bank-accounts', CustomerBankAccountController::class);
    Route::post('bank-accounts/global-add-bank-account', [CustomerBankAccountController::class, 'globalAddBankAccount'])->name('bank-accounts.global-add-bank-account');

    // Budget
    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', [CustomerBudgetController::class, 'index'])->name('index');
        Route::put('update/{id}', [CustomerBudgetController::class, 'update'])->name('update');
        Route::put('reset-budget/{id}', [CustomerBudgetController::class, 'resetBudget'])->name('reset-budget');
        Route::get('edit-category-list', [CustomerBudgetController::class, 'editCategoryList'])->name('edit-category-list');
        Route::put('update-category-list', [CustomerBudgetController::class, 'updateCategoryList'])->name('update-category-list');
        Route::post('global-add-budget', [CustomerBudgetController::class, 'globalAddNewBudget'])->name('global-add-budget');
        Route::post('restart-period', [CustomerBudgetController::class, 'restartPeriod'])->name('restart-period');
    });

    // My Account
    Route::name('my-account.')->prefix('my-account')->group(function () {
        Route::get('/', [CustomerMyAccountController::class, 'index'])->name('index');
        Route::put('main-details-store/{id}', [CustomerMyAccountController::class, 'mainDetailsStore'])->name('main-details-store');
        Route::put('password-update-store/{id}', [CustomerMyAccountController::class, 'passwordUpdateStore'])->name('password-update-store');
        Route::delete('delete-account/{id}', [CustomerMyAccountController::class, 'destroy'])->name('delete-account');
    });

    // Recurring Payments
    Route::resource('recurring-payments', CustomerRecurringPayments::class);
    Route::post('recurring-payment/add-recurring-payment', [CustomerRecurringPayments::class, 'globalAddRecurringPayments'])->name('recurring-payment.add-recurring-payment');

    // Transactions
    Route::resource('transactions', CustomerTransactionController::class);
    Route::post('transactions/global-add-transaction', [CustomerTransactionController::class, 'globalAddTransaction'])->name('transactions.global-add-transaction');
    Route::post('transactions/global-fund-transfer', [CustomerTransactionController::class, 'globalFundTransfer'])->name('transactions.global-fund-transfer');
    Route::get('transactions-filter-by-bank/{bank}', [CustomerTransactionController::class, 'filterByBank'])->name('transactions.filter-by-bank');

    // Cambia la banca della singola transazione
    Route::put('transactions/{transaction}/bank', [CustomerBankAccountController::class, 'updateTransactionBank'])->name('transactions.bank.update');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super admin|admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class,'index'])->name('dashboard');

    Route::name('activity-log.')->prefix('activity-log')->group(function () {
        Route::get('/', [AdminActivityLogController::class, 'index'])->name('index');
        Route::post('clear', [AdminActivityLogController::class, 'clearActivityLog'])->name('clear');
    });

    Route::post('upload-image', [AdminImageUploadController::class, 'upload'])->name('upload-image')->middleware('web');
});

/*
|--------------------------------------------------------------------------
| Staff
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super admin|staff'])->name('staff.')->prefix('staff')->group(function () {
    Route::get('dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Auth scaffolding + Email verification
|--------------------------------------------------------------------------
*/
Auth::routes(['verify' => true]);

Route::get('/email/verify', fn () => view('auth.verify-email'))->middleware('auth')->name('verification.notice');

Route::get('email-verified', fn () => view('auth.email-verified'))->middleware(['auth'])->name('email-verified');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) abort(403);

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    Auth::login($user);
    return redirect('/email-verified');
})->middleware(['signed'])->name('verification.verify');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
