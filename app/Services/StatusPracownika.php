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
     * @return array{typ: string, label: string, budowa: ?string, do: ?string, kod: ?string,
     *               budowa_do: ?string, ostatni_pobyt_do: ?string}
     */
    public function dla(Contact $contact, ?string $dzien = null): array
    {
        $dzien = $dzien ?? Carbon::today()->toDateString();

        // Relacje mogą być już wczytane (listy) — wtedy liczymy w pamięci,
        // bez dokładania zapytania na każdy wiersz.
        $nieobecnosc = $contact->relationLoaded('holidays')
            ? $contact->holidays->first(fn (Holiday $h) => $this->obejmuje($h->start, $h->end, $dzien))
            : $contact->holidays()->with('shiftStatus')->coveringDate($dzien)->first();

        $pobyty = $contact->relationLoaded('workDates')
            ? $contact->workDates
            : $contact->workDates()->with('organization')->get();

        $pobyt = $pobyty->first(fn (ContactWorkDate $w) => $this->obejmuje($w->start, $w->end, $dzien));
        $budowa = $pobyt ? optional($pobyt->organization)->nazwaBud : null;

        // Gdy nikogo nie ma na budowie dzisiaj — kiedy skończył ostatnio.
        $ostatniPobyt = $pobyt ? null : $pobyty
            ->filter(fn (ContactWorkDate $w) => $w->end !== null && $w->end < $dzien)
            ->sortByDesc('end')
            ->first();

        if ($nieobecnosc) {
            return [
                'typ' => 'nieobecnosc',
                'label' => $nieobecnosc->shiftStatus->title ?? 'Nieobecność',
                'kod' => $nieobecnosc->shiftStatus->code ?? null,
                'do' => $nieobecnosc->end,
                'budowa' => $budowa,
                // Koniec pobytu na budowie to inna data niż koniec nieobecności.
                'budowa_do' => $pobyt ? $pobyt->end : null,
                'ostatni_pobyt_do' => optional($ostatniPobyt)->end,
            ];
        }

        if ($budowa) {
            return [
                'typ' => 'budowa',
                'label' => $budowa,
                'kod' => null,
                'do' => $pobyt->end,
                'budowa' => $budowa,
                'budowa_do' => $pobyt->end,
                'ostatni_pobyt_do' => null,
            ];
        }

        return [
            'typ' => 'brak',
            'label' => 'Nie pracuje',
            'kod' => null,
            'do' => null,
            'budowa' => null,
            'budowa_do' => null,
            'ostatni_pobyt_do' => optional($ostatniPobyt)->end,
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
            // Bez zawężania do dziś — potrzebny też ostatni zakończony pobyt.
            'workDates' => fn ($query) => $query->with('organization'),
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
