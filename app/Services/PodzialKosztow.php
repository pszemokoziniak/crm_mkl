<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Koszt;
use App\Models\KosztOsoba;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Ile z kosztów budowy w miesiącu przypada na każdą osobę.
 *
 * Koszt z pracownikiem idzie w całości na niego. Koszt budowy bez podziału
 * ląduje w kubełku "nieprzypisane". Pokój dzieli się po osobodobach
 * zakwaterowanych; inny dzielony koszt — po wskazanych osobach po równo,
 * a gdy nikogo nie wskazano, po dniach pobytu na budowie w tym miesiącu.
 */
class PodzialKosztow
{
    /** Klucz kubełka kosztów, których nie da się przypisać do osoby. */
    public const NIEPRZYPISANE = 0;

    /**
     * @return Collection<int, array{contact_id: int|null, pracownik: string, kwota_pln: float, pozycje: array}>
     */
    public function dlaBudowy(Organization $budowa, string $miesiac): Collection
    {
        [$mOd, $mDo] = Koszt::zakresMiesiaca($miesiac);

        $koszty = Koszt::with(['typ', 'contact', 'osoby'])
            ->where('organization_id', $budowa->id)
            ->wMiesiacu($miesiac)
            ->orderBy('data')
            ->get();

        $wiersze = [];

        foreach ($koszty as $koszt) {
            [$udzialy, $sposob] = $this->udzialy($koszt, $mOd, $mDo);

            foreach ($udzialy as $contactId => $kwota) {
                $wiersze[$contactId]['kwota_pln'] = round(($wiersze[$contactId]['kwota_pln'] ?? 0) + $kwota, 2);
                $wiersze[$contactId]['pozycje'][] = [
                    'koszt_id' => $koszt->id,
                    'data' => $koszt->data?->format('Y-m-d'),
                    'typ' => optional($koszt->typ)->nazwa,
                    'opis' => $koszt->opis,
                    'calosc_pln' => $koszt->kwota_pln,
                    'kwota_pln' => $kwota,
                    'sposob' => $sposob,
                ];
            }
        }

        $nazwy = Contact::withTrashed()
            ->whereIn('id', array_filter(array_keys($wiersze)))
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        return collect($wiersze)
            ->map(fn (array $w, int $contactId) => [
                'contact_id' => $contactId ?: null,
                'pracownik' => $contactId
                    ? trim(optional($nazwy->get($contactId))->last_name.' '.optional($nazwy->get($contactId))->first_name)
                    : 'Nieprzypisane do osób',
                'kwota_pln' => $w['kwota_pln'],
                'pozycje' => $w['pozycje'],
            ])
            ->sortBy([fn ($a, $b) => ($a['contact_id'] === null) <=> ($b['contact_id'] === null), ['kwota_pln', 'desc']])
            ->values();
    }

    /**
     * Udziały pracownika w dzielonych kosztach budów w miesiącu — bez jego
     * własnych wpisów, bo te pokazuje osobna lista.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function dlaPracownika(Contact $pracownik, string $miesiac): Collection
    {
        [$mOd, $mDo] = Koszt::zakresMiesiaca($miesiac);

        $budowyZPobytu = ContactWorkDate::where('contact_id', $pracownik->id)
            ->where('start', '<=', $mDo)
            ->where(fn ($q) => $q->whereNull('end')->orWhere('end', '>=', $mOd))
            ->pluck('organization_id');

        $budowyZOsob = KosztOsoba::where('contact_id', $pracownik->id)
            ->whereHas('koszt', fn ($q) => $q->wMiesiacu($miesiac))
            ->with('koszt:id,organization_id')
            ->get()
            ->pluck('koszt.organization_id');

        $budowy = Organization::withTrashed()
            ->whereIn('id', $budowyZPobytu->merge($budowyZOsob)->filter()->unique())
            ->get();

        $pozycje = collect();

        foreach ($budowy as $budowa) {
            $wiersz = $this->dlaBudowy($budowa, $miesiac)->firstWhere('contact_id', $pracownik->id);

            if (! $wiersz) {
                continue;
            }

            foreach ($wiersz['pozycje'] as $p) {
                if ($p['sposob'] === 'koszt osoby') {
                    continue;
                }

                $pozycje->push($p + ['budowa' => $budowa->nazwaBud, 'organization_id' => $budowa->id]);
            }
        }

        return $pozycje->sortBy('data')->values();
    }

    /**
     * @return array{0: array<int, float>, 1: string} contact_id => kwota (0 = nieprzypisane), sposób podziału
     */
    private function udzialy(Koszt $koszt, string $mOd, string $mDo): array
    {
        if ($koszt->contact_id) {
            return [[(int) $koszt->contact_id => $koszt->kwota_pln], 'koszt osoby'];
        }

        if (! $koszt->dzielony) {
            return [[self::NIEPRZYPISANE => $koszt->kwota_pln], 'koszt budowy bez podziału'];
        }

        if ($koszt->jestNoclegiem()) {
            $wagi = $this->osobodoby($koszt);

            return $wagi
                ? [$this->rozdziel($koszt->kwota_pln, $wagi), 'osobodoby w pokoju']
                : [[self::NIEPRZYPISANE => $koszt->kwota_pln], 'pokój bez zakwaterowanych'];
        }

        if ($koszt->osoby->isNotEmpty()) {
            $wagi = $koszt->osoby->pluck('contact_id')->unique()->mapWithKeys(fn ($id) => [(int) $id => 1])->all();

            return [$this->rozdziel($koszt->kwota_pln, $wagi), 'wskazane osoby po równo'];
        }

        $wagi = $this->dniPobytu((int) $koszt->organization_id, $mOd, $mDo);

        return $wagi
            ? [$this->rozdziel($koszt->kwota_pln, $wagi), 'dni pobytu na budowie']
            : [[self::NIEPRZYPISANE => $koszt->kwota_pln], 'nikt na budowie w tym miesiącu'];
    }

    /** @return array<int, int> contact_id => liczba dni w pokoju (w okresie kosztu) */
    private function osobodoby(Koszt $koszt): array
    {
        $wagi = [];

        foreach ($koszt->osoby as $osoba) {
            $dni = $this->dniPrzeciecia(
                $osoba->od?->toDateString() ?? $koszt->od?->toDateString(),
                $osoba->do?->toDateString() ?? $koszt->do?->toDateString(),
                $koszt->od?->toDateString(),
                $koszt->do?->toDateString(),
            );

            if ($dni > 0) {
                $wagi[(int) $osoba->contact_id] = ($wagi[(int) $osoba->contact_id] ?? 0) + $dni;
            }
        }

        return $wagi;
    }

    /** @return array<int, int> contact_id => dni pobytu na budowie w miesiącu */
    private function dniPobytu(int $organizationId, string $mOd, string $mDo): array
    {
        $wagi = [];

        $pobyty = ContactWorkDate::where('organization_id', $organizationId)
            ->where('start', '<=', $mDo)
            ->where(fn ($q) => $q->whereNull('end')->orWhere('end', '>=', $mOd))
            ->get(['contact_id', 'start', 'end']);

        foreach ($pobyty as $pobyt) {
            $dni = $this->dniPrzeciecia((string) $pobyt->start, $pobyt->end ? (string) $pobyt->end : null, $mOd, $mDo);

            if ($dni > 0) {
                $wagi[(int) $pobyt->contact_id] = ($wagi[(int) $pobyt->contact_id] ?? 0) + $dni;
            }
        }

        return $wagi;
    }

    /** Liczba dni (włącznie z brzegami) wspólnych dla dwóch zakresów; null = bez końca. */
    private function dniPrzeciecia(?string $aOd, ?string $aDo, ?string $bOd, ?string $bDo): int
    {
        if (! $aOd || ! $bOd) {
            return 0;
        }

        $od = Carbon::parse(max(substr($aOd, 0, 10), substr($bOd, 0, 10)));
        $konce = array_filter([$aDo ? substr($aDo, 0, 10) : null, $bDo ? substr($bDo, 0, 10) : null]);

        if (! $konce) {
            return 0;
        }

        $do = Carbon::parse(min($konce));

        return $do->lt($od) ? 0 : (int) $od->diffInDays($do, true) + 1;
    }

    /**
     * Dzieli kwotę proporcjonalnie do wag, co do grosza: reszta z zaokrągleń
     * idzie do tych, którym najwięcej obcięto, więc suma udziałów = kwota.
     *
     * @param  array<int, int|float>  $wagi
     * @return array<int, float>
     */
    private function rozdziel(float $kwota, array $wagi): array
    {
        $suma = array_sum($wagi);
        $grosze = (int) round($kwota * 100);
        $przydzial = [];
        $reszty = [];
        $rozdane = 0;

        foreach ($wagi as $id => $waga) {
            $dokladnie = $grosze * $waga / $suma;
            $przydzial[$id] = (int) floor($dokladnie);
            $reszty[$id] = $dokladnie - $przydzial[$id];
            $rozdane += $przydzial[$id];
        }

        arsort($reszty);

        foreach (array_keys($reszty) as $id) {
            if ($rozdane >= $grosze) {
                break;
            }
            $przydzial[$id]++;
            $rozdane++;
        }

        return array_map(fn (int $g) => $g / 100, $przydzial);
    }
}
