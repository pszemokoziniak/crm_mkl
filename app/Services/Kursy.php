<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\KursWaluty;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kurs średni NBP (tabela A) z dnia kosztu; gdy to weekend albo święto,
 * z ostatniego dnia roboczego przed nim. Pobrane kursy zostają w bazie,
 * więc API pytamy raz na walutę i dzień, a stare wpisy nie pływają.
 */
class Kursy
{
    public const WALUTY = ['PLN', 'EUR', 'CHF', 'CZK', 'GBP'];

    /** Ile dni wstecz szukamy ostatniego notowania (weekend + długie święta). */
    private const ZASIEG_DNI = 10;

    private const NBP = 'https://api.nbp.pl/api/exchangerates/rates/A';

    /**
     * @return array{kurs: float, data: string}
     *
     * @throws BrakKursuException
     */
    public function kurs(string $waluta, string $data): array
    {
        $waluta = strtoupper($waluta);

        if ($waluta === 'PLN') {
            return ['kurs' => 1.0, 'data' => $data];
        }

        if ($z = $this->zBazy($waluta, $data)) {
            return $z;
        }

        $this->pobierzZNbp($waluta, $data);

        if ($z = $this->zBazy($waluta, $data)) {
            return $z;
        }

        throw new BrakKursuException("Brak kursu NBP dla {$waluta} na {$data}.");
    }

    /** @return array{kurs: float, data: string}|null */
    private function zBazy(string $waluta, string $data): ?array
    {
        $od = Carbon::parse($data)->subDays(self::ZASIEG_DNI)->toDateString();

        $wpis = KursWaluty::where('waluta', $waluta)
            ->whereBetween('data', [$od, $data])
            ->orderByDesc('data')
            ->first();

        return $wpis ? ['kurs' => (float) $wpis->kurs, 'data' => $wpis->data->toDateString()] : null;
    }

    private function pobierzZNbp(string $waluta, string $data): void
    {
        $do = Carbon::parse($data)->min(Carbon::today());
        $od = $do->copy()->subDays(self::ZASIEG_DNI);

        try {
            $odpowiedz = Http::timeout(10)
                ->acceptJson()
                ->get(self::NBP."/{$waluta}/{$od->toDateString()}/{$do->toDateString()}/", ['format' => 'json']);
        } catch (\Throwable $e) {
            Log::warning('NBP nie odpowiada: '.$e->getMessage(), ['waluta' => $waluta, 'data' => $data]);

            return;
        }

        if (! $odpowiedz->ok()) {
            return;
        }

        foreach ($odpowiedz->json('rates') ?? [] as $notowanie) {
            KursWaluty::updateOrCreate(
                ['waluta' => $waluta, 'data' => $notowanie['effectiveDate']],
                ['kurs' => $notowanie['mid'], 'zrodlo' => 'nbp'],
            );
        }
    }
}
