<?php

use App\Http\Controllers\AccountSetupController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminImageUploadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customer\AccountSetupInvestmentsController;
use App\Http\Controllers\Customer\CustomerBankAccountController;
use App\Http\Controllers\Customer\CustomerBudgetController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerMyAccountController;
use App\Http\Controllers\Customer\CustomerRecurringPayments;
use App\Http\Controllers\Customer\CustomerTransactionController;
use App\Http\Controllers\PlaidController;
use App\Http\Controllers\Staff\StaffDashboardController;
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
| Plaid
|--------------------------------------------------------------------------
*/

Route::get('/plaid-link', function () {
    return view('plaid-link');
})->name('plaid.link.page');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/plaid-link-demo', 'plaid.demo')->name('plaid.demo');

    Route::post('/plaid/link-token', [PlaidController::class, 'createLinkToken'])->name('plaid.link-token');
    Route::post('/plaid/exchange',    [PlaidController::class, 'exchangePublicToken'])->name('plaid.exchange');

    Route::view('/plaid/oauth-return', 'plaid.oauth-return')->name('plaid.oauth.return');
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
    Route::post('account-setup-step-two-store', [AccountSetupController::class, 'stepTwoStore'])->name('account-setup-step-two-store');

    Route::get('account-setup-step-three', [AccountSetupController::class, 'stepThreeShow'])->name('account-setup.step-three');
    Route::post('account-setup-step-three-store', [AccountSetupController::class, 'stepThreeStore'])->name('account-setup-step-three-store');

    Route::get('account-setup-step-four', [AccountSetupController::class, 'stepFourShow'])->name('account-setup.step-four');
    Route::post('account-setup-step-four-store', [AccountSetupController::class, 'stepFourStore'])->name('account-setup-step-four-store');

    Route::get('account-setup-step-five', [AccountSetupController::class, 'stepFiveShow'])->name('account-setup.step-five');
    Route::post('account-setup-step-five-store', [AccountSetupController::class, 'stepFiveStore'])->name('account-setup.step-five-store');

    // Step 6 (bank accounts)
    Route::get('account-setup-step-six', [AccountSetupController::class, 'stepSixShow'])->name('account-setup.step-six');

    // Step 6 (investments & pensions) - controller dedicato
    Route::get('account-setup-step-six-investments', [AccountSetupInvestmentsController::class, 'index'])
        ->name('account-setup.step-six-investments');

    Route::post('account-setup-step-six-investments', [AccountSetupInvestmentsController::class, 'store'])
        ->name('account-setup.step-six-investments-store');

    Route::put('account-setup-step-six-investments/{id}', [AccountSetupInvestmentsController::class, 'update'])
        ->name('account-setup.step-six-investments-update');

    Route::delete('account-setup-step-six-investments/{id}', [AccountSetupInvestmentsController::class, 'destroy'])
        ->name('account-setup.step-six-investments-destroy');

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
    Route::post('bank-accounts/global-add-bank-account', [CustomerBankAccountController::class, 'globalAddBankAccount'])
        ->name('bank-accounts.global-add-bank-account');

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
    Route::post('recurring-payment/add-recurring-payment', [CustomerRecurringPayments::class, 'globalAddRecurringPayments'])
        ->name('recurring-payment.add-recurring-payment');

    // Transactions
    Route::resource('transactions', CustomerTransactionController::class);
    Route::post('transactions/global-add-transaction', [CustomerTransactionController::class, 'globalAddTransaction'])->name('transactions.global-add-transaction');
    Route::post('transactions/global-fund-transfer', [CustomerTransactionController::class, 'globalFundTransfer'])->name('transactions.global-fund-transfer');
    Route::get('transactions-filter-by-bank/{bank}', [CustomerTransactionController::class, 'filterByBank'])->name('transactions.filter-by-bank');

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
| Auth + Email verification
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
