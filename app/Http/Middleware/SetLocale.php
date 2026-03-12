<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    protected array $supportedLocales = ['it', 'en', 'es', 'fr', 'pt', 'de'];

    public function handle(Request $request, Closure $next)
    {
        $locale = Session::get('locale', config('app.locale', 'en'));

        if (in_array($locale, $this->supportedLocales)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
