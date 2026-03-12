<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PowensService
{
    private Client $http;
    private string $domain;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->domain       = Config::get('services.powens.domain', 'clearcash-sandbox.biapi.pro');
        $this->clientId     = Config::get('services.powens.client_id', '');
        $this->clientSecret = Config::get('services.powens.client_secret', '');

        $this->http = new Client([
            'base_uri' => "https://{$this->domain}/2.0/",
            'timeout'  => 30,
        ]);
    }

    /**
     * Crea un utente Powens e restituisce id + token permanente.
     */
    public function createUser(): array
    {
        $res = $this->http->post('auth/init', [
            'json' => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $json = json_decode((string) $res->getBody(), true);

        return [
            'user_id' => $json['id_user'] ?? null,
            'token'   => $json['auth_token'] ?? null,
        ];
    }

    /**
     * Scambia il code ricevuto dal callback per un token permanente.
     */
    public function exchangeCode(string $code): array
    {
        $res = $this->http->post('auth/token/access', [
            'json' => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code'          => $code,
            ],
        ]);

        $json = json_decode((string) $res->getBody(), true);

        return [
            'user_id' => $json['id_user'] ?? null,
            'token'   => $json['access_token'] ?? null,
        ];
    }

    /**
     * Genera l'URL della Webview per collegare una banca.
     */
    public function getConnectUrl(string $userToken, string $redirectUri): string
    {
        $params = http_build_query([
            'domain'       => $this->domain,
            'redirect_uri' => $redirectUri,
            'client_id'    => $this->clientId,
        ]);

        return "https://webview.powens.com/connect?{$params}&token={$userToken}";
    }

    /**
     * Recupera tutte le connessioni bancarie dell'utente.
     */
    public function getConnections(string $userToken): array
    {
        $res = $this->http->get('users/me/connections', [
            'headers' => ['Authorization' => "Bearer {$userToken}"],
            'query'   => ['expand' => 'connector'],
        ]);

        $json = json_decode((string) $res->getBody(), true);

        return $json['connections'] ?? [];
    }

    /**
     * Recupera tutti i conti bancari dell'utente.
     */
    public function getAccounts(string $userToken): array
    {
        $res = $this->http->get('users/me/accounts', [
            'headers' => ['Authorization' => "Bearer {$userToken}"],
        ]);

        $json = json_decode((string) $res->getBody(), true);

        return $json['accounts'] ?? [];
    }

    /**
     * Recupera le transazioni dell'utente.
     * Opzionalmente filtra per account e per data.
     */
    public function getTransactions(string $userToken, ?int $accountId = null, ?string $minDate = null, ?string $maxDate = null): array
    {
        $query = ['limit' => 1000];

        if ($accountId) {
            $query['id_account'] = $accountId;
        }
        if ($minDate) {
            $query['min_date'] = $minDate;
        }
        if ($maxDate) {
            $query['max_date'] = $maxDate;
        }

        $res = $this->http->get('users/me/transactions', [
            'headers' => ['Authorization' => "Bearer {$userToken}"],
            'query'   => $query,
        ]);

        $json = json_decode((string) $res->getBody(), true);

        return $json['transactions'] ?? [];
    }

    /**
     * Forza un refresh della connessione bancaria.
     */
    public function syncConnection(string $userToken, int $connectionId): array
    {
        $res = $this->http->put("users/me/connections/{$connectionId}", [
            'headers' => ['Authorization' => "Bearer {$userToken}"],
        ]);

        return json_decode((string) $res->getBody(), true);
    }

    /**
     * Elimina una connessione bancaria.
     */
    public function deleteConnection(string $userToken, int $connectionId): void
    {
        $this->http->delete("users/me/connections/{$connectionId}", [
            'headers' => ['Authorization' => "Bearer {$userToken}"],
        ]);
    }
}
