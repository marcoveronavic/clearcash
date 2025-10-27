<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class PlaidClient
{
    private string $base;
    private mixed $verify;

    public function __construct()
    {
        $env = (string) env('PLAID_ENV', 'sandbox');

        $this->base = match ($env) {
            'production'  => 'https://production.plaid.com',
            'development' => 'https://development.plaid.com',
            default       => 'https://sandbox.plaid.com',
        };

        // Toggle SSL verify (solo DEV/locale puoi tenerlo false)
        $toggle = env('PLAID_VERIFY_SSL', true);
        if ($toggle === false || $toggle === 'false' || $toggle === 0 || $toggle === '0') {
            $this->verify = false;
        } else {
            $this->verify = env('CURL_CA_BUNDLE') ?: env('SSL_CERT_FILE') ?: true;
        }
    }

    public function post(string $path, array $body = []): array
    {
        $payload = array_merge([
            'client_id' => env('PLAID_CLIENT_ID'),
            'secret'    => env('PLAID_SECRET'),
        ], $body);

        return Http::withOptions(['verify' => $this->verify])
            // retry: fino a 5 tentativi; 1500ms tra i tentativi;
            // se 429 e c'è Retry-After, lo rispetta
            ->retry(5, 1500, function ($exception, $request) {
                if ($exception instanceof RequestException) {
                    $resp = $exception->response;
                    if ($resp && $resp->status() === 429) {
                        $retryAfter = $resp->header('Retry-After');
                        if (is_numeric($retryAfter)) {
                            sleep((int)$retryAfter);
                        }
                        return true; // ritenta
                    }
                }
                return false;
            })
            ->asJson()
            ->post($this->base . $path, $payload)
            ->throw()
            ->json();
    }

    public function baseUrl(): string
    {
        return $this->base;
    }
}
