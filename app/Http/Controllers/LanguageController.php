<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    protected array $supportedLocales = ['it', 'en', 'es', 'fr', 'pt', 'de'];

    public function switchLocale(string $locale)
    {
        if (in_array($locale, $this->supportedLocales)) {
            Session::put('locale', $locale);
            Session::put('locale_chosen', true);
        }

        return redirect()->back();
    }
}
