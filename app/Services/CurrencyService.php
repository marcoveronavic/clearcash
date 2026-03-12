<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Recupera il tasso di cambio da $from a $to.
     * Usa l'API gratuita di ExchangeRate-API (no key necessaria per il tier open).
     * Cachea il risultato per 1 ora.
     */
    public function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($from, $to) {
            try {
                $response = Http::timeout(5)->get(
                    "https://open.er-api.com/v6/latest/{$from}"
                );

                if ($response->successful()) {
                    $rates = $response->json('rates');
                    return (float) ($rates[$to] ?? 1.0);
                }
            } catch (\Exception $e) {
                Log::warning("CurrencyService: impossibile recuperare tasso {$from}->{$to}: " . $e->getMessage());
            }

            return 1.0; // fallback sicuro
        });
    }

    /**
     * Converte un importo da $from a $to.
     * Restituisce ['amount' => float, 'rate' => float]
     */
    public function convert(float $amount, string $from, string $to): array
    {
        $rate = $this->getRate($from, $to);

        return [
            'amount' => round($amount * $rate, 4),
            'rate'   => $rate,
        ];
    }

    /**
     * Simbolo visuale per una currency code.
     */
    public function symbol(string $currency): string
    {
        return match($currency) {
            'GBP'   => '£',
            'EUR'   => '€',
            'USD'   => '$',
            'CHF'   => 'CHF',
            'JPY'   => '¥',
            default => $currency,
        };
    }
}
