<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wysyłka SMS przez SMSAPI.pl. Bez tokenu w konfiguracji (SMSAPI_TOKEN)
 * wysyłka jest wyłączona — ekran mówi o tym wprost, zamiast udawać.
 */
class Sms
{
    public function skonfigurowany(): bool
    {
        return (string) config('services.smsapi.token') !== '';
    }

    /**
     * @throws \RuntimeException gdy bramka odmówi
     */
    public function wyslij(string $numer, string $tresc): void
    {
        if (! $this->skonfigurowany()) {
            throw new \RuntimeException('Wysyłka SMS nie jest skonfigurowana (brak SMSAPI_TOKEN).');
        }

        $odpowiedz = Http::withToken((string) config('services.smsapi.token'))
            ->asForm()
            ->post('https://api.smsapi.pl/sms.do', [
                'to' => self::numer($numer),
                'message' => $tresc,
                'from' => (string) config('services.smsapi.nadawca', 'Info'),
                'format' => 'json',
                'encoding' => 'utf-8',
            ]);

        $dane = $odpowiedz->json();
        if (! $odpowiedz->ok() || isset($dane['error'])) {
            Log::warning('SMSAPI odmówiło wysyłki', ['numer' => $numer, 'odpowiedz' => $dane]);
            throw new \RuntimeException('Bramka SMS odmówiła: '.($dane['message'] ?? $odpowiedz->status()));
        }
    }

    /** Numer w formacie bramki: same cyfry, polski bez kierunkowego dostaje 48. */
    public static function numer(string $numer): string
    {
        $cyfry = preg_replace('/\D+/', '', $numer) ?? '';

        return strlen($cyfry) === 9 ? '48'.$cyfry : $cyfry;
    }
}
