<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BuildingTimeSheet;
use App\Models\Organization;
use App\Models\ShiftStatus;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Podsumowanie budowy liczone z Karty Czasu Pracy.
 *
 * Jeden wpis KCP to jeden dzień jednego pracownika na jednej budowie:
 * okno zmiany (work_from – work_to), czas efektywny (effective_work_time)
 * i opcjonalny status (urlop, zwolnienie…). Brak statusu = zwykła praca.
 *
 * O tym, czy status to urlop czy zwolnienie, decyduje kategoria ze słownika,
 * a nie jego nazwa ani numer — słownik prowadzi biuro i bywa w nim wszystko.
 */
class StatystykiBudow
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function dlaBudow(?User $user, ?int $rok = null, bool $zArchiwum = true): array
    {
        // Domyślnie z archiwum: prawie cała historia godzin należy do budów
        // już zamkniętych i to właśnie tam jest podsumowanie kontraktu.
        // Bez tego ekran pokazywałby jedną budowę zamiast kilkunastu.
        $budowy = Organization::query()
            ->when($zArchiwum, fn ($q) => $q->withTrashed())
            ->tylkoBudowy()
            ->visibleTo($user)
            ->orderBy('nazwaBud')
            ->get(['id', 'nazwaBud', 'deleted_at']);

        if ($budowy->isEmpty()) {
            return [];
        }

        $wpisy = BuildingTimeSheet::query()
            ->whereIn('organization_id', $budowy->pluck('id'))
            ->when($rok, fn ($q) => $q->whereYear('work_day', $rok))
            ->get(['organization_id', 'contact_id', 'work_day', 'work_from', 'work_to', 'effective_work_time', 'shift_status_id']);

        $kategorie = $this->kategorieStatusow();

        return $budowy
            ->map(fn (Organization $b) => $this->podsumuj($b, $wpisy->where('organization_id', $b->id), $kategorie))
            ->all();
    }

    /** Lata, dla których w ogóle są wpisy — do listy wyboru okresu. */
    public function dostepneLata(): array
    {
        return BuildingTimeSheet::query()
            ->selectRaw('DISTINCT YEAR(work_day) as rok')
            ->orderByDesc('rok')
            ->pluck('rok')
            ->map(fn ($r) => (int) $r)
            ->all();
    }

    /**
     * @param  Collection<int, BuildingTimeSheet>  $wpisy
     * @return array<string, mixed>
     */
    private function podsumuj(Organization $budowa, Collection $wpisy, array $kategorie): array
    {
        $godziny = [
            'praca' => 0.0, 'urlop' => 0.0, 'zwolnienie' => 0.0,
            'nieobecnosc' => 0.0, 'swieto' => 0.0, 'inne' => 0.0,
        ];
        $przerwy = 0.0;
        $dniPracy = 0;

        foreach ($wpisy as $w) {
            $efektywne = $this->naGodziny($w->effective_work_time);

            // Brak statusu = zwykła praca. Status z kategorią "praca" (np. praca
            // w biurze) też liczy się jako przepracowany czas.
            $kategoria = $w->shift_status_id === null
                ? 'praca'
                : ($kategorie[$w->shift_status_id] ?? 'inne');

            $godziny[$kategoria] = ($godziny[$kategoria] ?? 0) + $efektywne;

            if ($kategoria === 'praca' && $efektywne > 0) {
                $dniPracy++;
                // Różnica między oknem zmiany a czasem efektywnym: przerwy
                // i postoje. To druga możliwa odpowiedź na "godziny zmarnowane".
                $okno = $this->dlugoscZmiany($w->work_from, $w->work_to);
                if ($okno > $efektywne) {
                    $przerwy += $okno - $efektywne;
                }
            }
        }

        $osoby = $wpisy->pluck('contact_id')->unique()->count();

        return [
            'id' => $budowa->id,
            'nazwa' => $budowa->nazwaBud,
            'archiwum' => $budowa->deleted_at !== null,
            'pracownikow' => $osoby,
            'dni_pracy' => $dniPracy,
            'godziny' => array_map(fn ($g) => round($g, 1), $godziny),
            'przerwy' => round($przerwy, 1),
            'razem_rozliczone' => round(array_sum($godziny), 1),
            // Ile średnio wychodzi na osobodzień — od razu widać dzień
            // ośmiogodzinny od dziesięciogodzinnego.
            'srednia_dniowka' => $dniPracy > 0 ? round($godziny['praca'] / $dniPracy, 1) : null,
        ];
    }

    /** @return array<int, string> id statusu => kategoria */
    private function kategorieStatusow(): array
    {
        $mapa = [];

        foreach (array_keys(ShiftStatus::KATEGORIE) as $kategoria) {
            foreach (ShiftStatus::idsKategorii($kategoria) as $id) {
                $mapa[$id] = $kategoria;
            }
        }

        return $mapa;
    }

    /** "09:30" → 9.5. Pole jest tekstem, więc puste i śmieci traktujemy jak zero. */
    private function naGodziny(?string $czas): float
    {
        if (! $czas || ! str_contains($czas, ':')) {
            return 0.0;
        }

        [$h, $m] = array_pad(explode(':', $czas, 2), 2, '0');

        return (int) $h + ((int) $m / 60);
    }

    private function dlugoscZmiany($od, $do): float
    {
        if (! $od || ! $do) {
            return 0.0;
        }

        $sekundy = strtotime((string) $do) - strtotime((string) $od);

        return $sekundy > 0 ? $sekundy / 3600 : 0.0;
    }
}
