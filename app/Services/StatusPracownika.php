<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Holiday;
use Carbon\Carbon;

/**
 * Co pracownik robi w danym dniu: jest na budowie, jest nieobecny (urlop,
 * zwolnienie), czy jedno i drugie naraz — nieobecność nie zdejmuje go z budowy.
 *
 * Zwracana struktura trafia na listę pracowników, kartę pracownika i listę
 * pracowników budowy, więc kształt jest jeden dla wszystkich trzech miejsc.
 */
class StatusPracownika
{
    /**
     * @return array{typ: string, label: string, budowa: ?string, do: ?string, kod: ?string}
     */
    public function dla(Contact $contact, ?string $dzien = null): array
    {
        $dzien = $dzien ?? Carbon::today()->toDateString();

        // Relacje mogą być już wczytane (listy) — wtedy liczymy w pamięci,
        // bez dokładania zapytania na każdy wiersz.
        $nieobecnosc = $contact->relationLoaded('holidays')
            ? $contact->holidays->first(fn (Holiday $h) => $this->obejmuje($h->start, $h->end, $dzien))
            : $contact->holidays()->with('shiftStatus')->coveringDate($dzien)->first();

        $pobyt = $contact->relationLoaded('workDates')
            ? $contact->workDates->first(fn (ContactWorkDate $w) => $this->obejmuje($w->start, $w->end, $dzien))
            : $contact->workDates()->with('organization')->activeOn($dzien)->first();

        $budowa = $pobyt ? optional($pobyt->organization)->nazwaBud : null;

        if ($nieobecnosc) {
            return [
                'typ' => 'nieobecnosc',
                'label' => $nieobecnosc->shiftStatus->title ?? 'Nieobecność',
                'kod' => $nieobecnosc->shiftStatus->code ?? null,
                'do' => $nieobecnosc->end,
                'budowa' => $budowa,
            ];
        }

        if ($budowa) {
            return [
                'typ' => 'budowa',
                'label' => $budowa,
                'kod' => null,
                'do' => $pobyt->end,
                'budowa' => $budowa,
            ];
        }

        return [
            'typ' => 'brak',
            'label' => 'Nie pracuje',
            'kod' => null,
            'do' => null,
            'budowa' => null,
        ];
    }

    /**
     * Relacje do wczytania z góry, żeby lista nie robiła zapytań na wiersz.
     *
     * @return array<string, mixed>
     */
    public function relacjeDoListy(?string $dzien = null): array
    {
        $dzien = $dzien ?? Carbon::today()->toDateString();

        return [
            'holidays' => fn ($query) => $query->with('shiftStatus')->coveringDate($dzien),
            'workDates' => fn ($query) => $query->with('organization')->activeOn($dzien),
        ];
    }

    private function obejmuje(?string $start, ?string $end, string $dzien): bool
    {
        if ($start === null || $start > $dzien) {
            return false;
        }

        return $end === null || $end >= $dzien;
    }
}
