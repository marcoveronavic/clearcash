<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;

class PlaidService
{
    private Client $http;
    private string $baseUri;
    private string $clientId;
    private string $secret;

    public function __construct()
    {
        $env  = strtolower(Config::get('plaid.env', 'sandbox'));
        $base = Config::get('plaid.base_urls', [
            'sandbox'     => 'https://sandbox.plaid.com',
            'development' => 'https://development.plaid.com',
            'production'  => 'https://production.plaid.com',
        ]);

        $this->baseUri  = $base[$env] ?? $base['sandbox'];
        $this->clientId = (string) Config::get('plaid.client_id');
        $this->secret   = (string) Config::get('plaid.secret');

        $this->http = new Client([
            'base_uri' => $this->baseUri,
            'timeout'  => 20,
        ]);
    }

    public function createLinkToken(int|string $userId, ?string $clientName = null, string $language = 'en'): string
    {
        $payload = [
            'client_id'     => $this->clientId,
            'secret'        => $this->secret,
            'user'          => ['client_user_id' => (string) $userId],
            'client_name'   => $clientName ?? config('app.name', 'ClearCash'),
            'products'      => Config::get('plaid.products', ['transactions']),
            'country_codes' => Config::get('plaid.countries', ['GB']),
            'language'      => $language,
        ];

        if ($redirect = Config::get('plaid.redirect')) {
            $payload['redirect_uri'] = $redirect;
        }

        $res  = $this->http->post('/link/token/create', ['json' => $payload]);
        $json = json_decode((string) $res->getBody(), true);

        return $json['link_token'] ?? '';
    }

    public function exchangePublicToken(string $publicToken): array
    {
        $payload = [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'public_token' => $publicToken,
        ];

        $res  = $this->http->post('/item/public_token/exchange', ['json' => $payload]);
        $json = json_decode((string) $res->getBody(), true);

        return [
            'access_token' => $json['access_token'] ?? null,
            'item_id'      => $json['item_id'] ?? null,
        ];
    }

    public function getAccounts(string $accessToken): array
    {
        $payload = [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'access_token' => $accessToken,
        ];

        $res  = $this->http->post('/accounts/get', ['json' => $payload]);
        $json = json_decode((string) $res->getBody(), true);

        return $json['accounts'] ?? [];
    }
}
