<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class YapilyController extends Controller
{
    // Elenco istituti (per debug; opzionale)
    public function institutions()
    {
        $res = Http::withBasicAuth(env('YAPILY_APP_KEY'), env('YAPILY_APP_SECRET'))
            ->get('https://api.yapily.com/institutions');

        return response()->json($res->json());
    }

    // Avvia l’autorizzazione AIS per una banca sandbox
    public function start(Request $request, string $institutionId)
    {
        $payload = [
            'applicationUserId' => 'user-'.Auth::id(),
            'institutionId'     => $institutionId,
            'callback'          => route('yapily.callback'),
            'oneTimeToken'      => true, // niente consent in chiaro nella URL
        ];

        $res = Http::withBasicAuth(env('YAPILY_APP_KEY'), env('YAPILY_APP_SECRET'))
            ->asJson()
            ->post('https://api.yapily.com/account-auth-requests', $payload)
            ->throw();

        $url = data_get($res->json(), 'data.authorisationUrl');
        abort_unless($url, 500, 'authorisationUrl non ricevuto da Yapily');

        return redirect()->away($url);
    }

    // Callback dopo l’SCA sul sandbox
    public function callback(Request $request)
    {
        // Se hai chiesto oneTimeToken, scambialo per il consentToken
        if ($request->filled('one-time-token')) {
            $exchange = Http::withBasicAuth(env('YAPILY_APP_KEY'), env('YAPILY_APP_SECRET'))
                ->post('https://api.yapily.com/consent-one-time-token', [
                    'oneTimeToken' => $request->query('one-time-token'),
                ])
                ->throw()
                ->json();

            $consentToken = data_get($exchange, 'data.consentToken');
        } else {
            $consentToken = $request->query('consent');
        }

        abort_unless($consentToken, 400, 'Consent token mancante nel callback');

        // Per lo smoke test teniamolo in sessione (in produzione: salvare a DB)
        Session::put('yapily.consent', $consentToken);

        return redirect()->route('yapily.accounts')
            ->with('success', 'Banca collegata correttamente.');
    }

    // Legge i conti collegati (usa il consent dalla sessione)
    public function accounts()
    {
        $consent = Session::get('yapily.consent');
        abort_unless($consent, 400, 'Devi prima collegare una banca.');

        $res = Http::withBasicAuth(env('YAPILY_APP_KEY'), env('YAPILY_APP_SECRET'))
            ->withHeaders(['Consent' => $consent])
            ->get('https://api.yapily.com/accounts')
            ->throw()
            ->json();

        return response()->json($res); // per ora JSON: ci basta per verificare
    }

    // Legge le transazioni di un account
    public function transactions(string $accountId)
    {
        $consent = Session::get('yapily.consent');
        abort_unless($consent, 400, 'Devi prima collegare una banca.');

        $res = Http::withBasicAuth(env('YAPILY_APP_KEY'), env('YAPILY_APP_SECRET'))
            ->withHeaders(['Consent' => $consent])
            ->get("https://api.yapily.com/accounts/{$accountId}/transactions", [
                'limit' => request('limit', 20),
            ])
            ->throw()
            ->json();

        return response()->json($res); // anche qui JSON per il test
    }
}
