<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\KrajTyp;
use Carbon\Carbon;

/**
 * Limit 183 dni pobytu w obcym państwie.
 *
 * Umowy o unikaniu podwójnego opodatkowania liczą dni nie w roku
 * kalendarzowym, tylko w KAŻDYM dwunastomiesięcznym okresie — liczonym
 * w przód od przyjazdu i wstecz od wyjazdu. Dlatego bierzemy największą
 * liczbę dni, jaka wypada w dowolnym oknie 365 dni, a nie sumę za rok.
 *
 * Liczymy całe pobyty, razem z dniem przyjazdu i wyjazdu. System nie wie,
 * czy ktoś wracał do domu na weekendy, więc wynik jest GÓRNĄ GRANICĄ:
 * nadaje się na ostrzeżenie, nie zastępuje wyliczenia księgowości.
 */
class LimitPobytuZagranica
{
    public const LIMIT_DNI = 183;
    public const OKNO_DNI = 365;

    /** Poniżej tylu dni zapasu zapala się ostrzeżenie. */
    public const PROG_UWAGI = 30;

    /**
     * Zestawienie dla pracownika: jeden wiersz na kraj, od najgorszego.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dlaPracownika(Contact $contact, ?string $dzien = null): array
    {
        $dzis = Carbon::parse($dzien ?? Carbon::today()->toDateString())->startOfDay();
        $kalendarz = $this->dniPobytuWgKraju($contact, $dzis);

        $wiersze = [];

        foreach ($kalendarz as $kraj => $dni) {
            ksort($dni);
            $lista = array_keys($dni);

            $wykorzystane = $this->wOknieDo($lista, $dzis);
            $pozostalo = max(0, self::LIMIT_DNI - $wykorzystane);
            [$najwieksze, $od, $do] = $this->najwiekszeOkno($lista);

            $wiersze[] = [
                'kraj' => $kraj,
                'dni_12m' => $wykorzystane,
                'pozostalo' => $pozostalo,
                'najwieksze_okno' => $najwieksze,
                'okno_od' => $od,
                'okno_do' => $do,
                'kiedys_przekroczony' => $najwieksze > self::LIMIT_DNI,
                'wolne_od' => $pozostalo === 0 ? $this->kiedyZwolniSie($lista, $dzis) : null,
                'status' => $this->status($pozostalo, $najwieksze),
            ];
        }

        usort($wiersze, fn ($a, $b) => $b['dni_12m'] <=> $a['dni_12m']);

        return $wiersze;
    }

    private function status(int $pozostalo, int $najwieksze): string
    {
        if ($pozostalo === 0 || $najwieksze > self::LIMIT_DNI) {
            return 'przekroczony';
        }

        return $pozostalo <= self::PROG_UWAGI ? 'uwaga' : 'ok';
    }

    /**
     * Dni pobytu w obcych państwach, bez powtórzeń — nakładające się pobyty
     * na dwóch budowach w tym samym kraju to wciąż jeden dzień za granicą.
     *
     * Kraj macierzysty rozpoznajemy po tym samym znaczniku, co przy A1:
     * gdzie A1 nie jest potrzebne, tam jesteśmy u siebie i limit nie biegnie.
     *
     * @return array<string, array<string, true>>
     */
    private function dniPobytuWgKraju(Contact $contact, Carbon $dzis): array
    {
        $macierzyste = KrajTyp::idsBezA1();
        $kalendarz = [];

        $pobyty = ContactWorkDate::with('organization.krajTyp')
            ->where('contact_id', $contact->id)
            ->whereNotNull('start')
            ->get();

        foreach ($pobyty as $pobyt) {
            $kraj = optional(optional($pobyt->organization)->krajTyp);

            if (! $kraj->name || in_array((int) $kraj->id, $macierzyste, true)) {
                continue;
            }

            $od = Carbon::parse($pobyt->start)->startOfDay();
            // Pobyt bez daty końca wciąż trwa; przyszłości nie liczymy jako
            // dni już wykorzystanych.
            $do = $pobyt->end ? Carbon::parse($pobyt->end)->startOfDay() : $dzis->copy();

            if ($do->gt($dzis)) {
                $do = $dzis->copy();
            }

            if ($do->lt($od)) {
                continue;
            }

            for ($dzien = $od->copy(); $dzien->lte($do); $dzien->addDay()) {
                $kalendarz[$kraj->name][$dzien->toDateString()] = true;
            }
        }

        return $kalendarz;
    }

    /** Ile z tych dni mieści się w oknie 365 dni kończącym się danego dnia. */
    private function wOknieDo(array $lista, Carbon $koniec): int
    {
        $poczatek = $koniec->copy()->subDays(self::OKNO_DNI - 1)->toDateString();
        $koniecTekst = $koniec->toDateString();

        return count(array_filter(
            $lista,
            fn (string $dzien) => $dzien >= $poczatek && $dzien <= $koniecTekst
        ));
    }

    /**
     * Najgorsze okno: tyle dni, ile najwięcej wypada w dowolnych kolejnych
     * 365 dniach. To jest liczba, o którą pyta urząd.
     *
     * @return array{0: int, 1: ?string, 2: ?string}
     */
    private function najwiekszeOkno(array $lista): array
    {
        $ile = count($lista);
        $najlepsze = 0;
        $od = null;
        $do = null;
        $lewy = 0;

        for ($prawy = 0; $prawy < $ile; $prawy++) {
            while (Carbon::parse($lista[$lewy])->diffInDays(Carbon::parse($lista[$prawy])) >= self::OKNO_DNI) {
                $lewy++;
            }

            if ($prawy - $lewy + 1 > $najlepsze) {
                $najlepsze = $prawy - $lewy + 1;
                $od = $lista[$lewy];
                $do = $lista[$prawy];
            }
        }

        return [$najlepsze, $od, $do];
    }

    /**
     * Kiedy limit sam się poluzuje: pierwszy dzień, w którym stare dni wypadną
     * z okna na tyle, że znów zmieści się choć jeden nowy.
     */
    private function kiedyZwolniSie(array $lista, Carbon $dzis): ?string
    {
        $dzien = $dzis->copy();
        $granica = $dzis->copy()->addDays(self::OKNO_DNI);

        while ($dzien->lte($granica)) {
            if ($this->wOknieDo($lista, $dzien) < self::LIMIT_DNI) {
                return $dzien->toDateString();
            }

            $dzien->addDay();
        }

        return null;
    }
}
