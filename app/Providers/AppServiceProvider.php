<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// use Illuminate\Support\Facades\URL; // ← non serve più

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
        // IMPORTANTE:
        // Rimuoviamo ogni forzatura della root URL.
        // Niente URL::forceRootUrl(), niente URL::forceScheme().
        // Così Laravel usa esattamente host+schema della richiesta corrente
        // e i cookie di sessione restano validi.
    }
}
