<?php

namespace App\Providers;

use App\Models\BankAccount;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(false);
        Model::preventAccessingMissingAttributes();
        Model::preventSilentlyDiscardingAttributes();

        View::composer('*', function ($view) {
           if(Auth::check()) {

               $categories = Budget::where('user_id', Auth::id())
                   ->where('amount', '>', 0)
                   ->with('category')
                   ->get();

               $uncategorised = Budget::where('user_id', Auth::id())
                   ->where('amount', 0)
                   ->where('category_name', 'uncategorised')
                   ->with('category')
                   ->get();

               $view->with('categories', $categories->merge($uncategorised));

               $bankAccounts = BankAccount::where('user_id', Auth::user()->id)
                   ->orderBy('account_name', 'asc')
                   ->where('account_name', '!=', 'pension')
                   ->get();

               $view->with('bankAccounts', $bankAccounts);
           }
        });

    }
}
