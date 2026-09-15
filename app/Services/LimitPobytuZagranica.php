<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\KrajTyp;
use App\Models\Organization;
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
                'status' => $this->status($pozostalo),
            ];
        }

        usort($wiersze, fn ($a, $b) => $b['dni_12m'] <=> $a['dni_12m']);

        return $wiersze;
    }

    /**
     * Stan mówi o dziś, nie o historii. Kto przekroczył próg dwa lata temu,
     * a teraz ma zapas 53 dni, może jechać — czerwień przy jego nazwisku
     * wstrzymywałaby wyjazd bez powodu. Dawne przekroczenie pokazujemy
     * osobno, przy najdłuższym oknie.
     */
    /**
     * Czy dopisanie pobytu przepełni limit w kraju tej budowy.
     *
     * Liczymy z dniami zaplanowanymi na przyszłość, bo pytanie brzmi "czy
     * wolno go tam wysłać", a nie "ile już wykorzystał". Zwraca null, gdy
     * jest w porządku albo gdy budowa jest w kraju macierzystym.
     *
     * @return array{kraj: string, dni: int, od: ?string, do: ?string}|null
     */
    public function przekroczenieDla(Contact $contact, Organization $budowa, string $od, string $do): ?array
    {
        $kraj = optional($budowa->krajTyp);

        if (! $kraj->name || in_array((int) $kraj->id, KrajTyp::idsBezA1(), true)) {
            return null;
        }

        $dni = [];

        foreach ($this->pobytyWKraju($contact, $kraj->name) as $pobyt) {
            foreach ($this->dniPobytu($pobyt, Carbon::today()->startOfDay()) as $dzien) {
                $dni[$dzien] = true;
            }
        }

        $poczatek = Carbon::parse($od)->startOfDay();
        $koniec = Carbon::parse($do)->startOfDay();

        for ($dzien = $poczatek->copy(); $dzien->lte($koniec); $dzien->addDay()) {
            $dni[$dzien->toDateString()] = true;
        }

        ksort($dni);
        [$najwieksze, $oknoOd, $oknoDo] = $this->najwiekszeOkno(array_keys($dni));

        if ($najwieksze <= self::LIMIT_DNI) {
            return null;
        }

        return ['kraj' => $kraj->name, 'dni' => $najwieksze, 'od' => $oknoOd, 'do' => $oknoDo];
    }

    /** @return \Illuminate\Support\Collection<int, ContactWorkDate> */
    private function pobytyWKraju(Contact $contact, string $kraj)
    {
        return ContactWorkDate::with('organization.krajTyp')
            ->where('contact_id', $contact->id)
            ->whereNotNull('start')
            ->get()
            ->filter(fn (ContactWorkDate $p) => optional(optional($p->organization)->krajTyp)->name === $kraj);
    }

    /**
     * Ci, którzy są dziś na budowie za granicą i mają już mało zapasu.
     * Jedno zapytanie na wszystkich — to samo liczone po kolei dla każdego
     * dokładałoby zapytanie na wiersz pulpitu.
     *
     * @param  array<int, int>|null  $tylkoPracownicy  null = wszyscy
     * @return array<int, array<string, mixed>>
     */
    public function zblizajacySieDoLimitu(?array $tylkoPracownicy = null, ?string $dzien = null, ?int $prog = null): array
    {
        $dzis = Carbon::parse($dzien ?? Carbon::today()->toDateString())->startOfDay();
        $prog = $prog ?? self::PROG_UWAGI;
        $macierzyste = KrajTyp::idsBezA1();

        // Kto jest dziś za granicą — tylko o nich pytamy dalej.
        $naBudowie = ContactWorkDate::with('organization.krajTyp', 'contact')
            ->whereHas('contact')
            ->when($tylkoPracownicy !== null, fn ($q) => $q->whereIn('contact_id', $tylkoPracownicy ?: [0]))
            ->activeOn($dzis->toDateString())
            ->get()
            ->filter(function (ContactWorkDate $pobyt) use ($macierzyste) {
                $kraj = optional(optional($pobyt->organization)->krajTyp);

                return $kraj->name && ! in_array((int) $kraj->id, $macierzyste, true);
            });

        if ($naBudowie->isEmpty()) {
            return [];
        }

        $kalendarze = $this->kalendarzeDlaPracownikow($naBudowie->pluck('contact_id')->unique()->all(), $dzis);
        $wiersze = [];

        foreach ($naBudowie as $pobyt) {
            $kraj = $pobyt->organization->krajTyp->name;
            $dni = $kalendarze[$pobyt->contact_id][$kraj] ?? [];

            if ($dni === []) {
                continue;
            }

            ksort($dni);
            $wykorzystane = $this->wOknieDo(array_keys($dni), $dzis);
            $pozostalo = max(0, self::LIMIT_DNI - $wykorzystane);

            if ($pozostalo > $prog) {
                continue;
            }

            $klucz = $pobyt->contact_id.'-'.$kraj;

            $wiersze[$klucz] = [
                'contact' => $pobyt->contact,
                'organization' => $pobyt->organization,
                'kraj' => $kraj,
                'dni_12m' => $wykorzystane,
                'pozostalo' => $pozostalo,
                // Dzień, w którym limit pęknie, jeśli pobyt potrwa bez przerwy.
                'przekroczy' => $dzis->copy()->addDays($pozostalo)->toDateString(),
                'status' => $this->status($pozostalo),
            ];
        }

        return array_values($wiersze);
    }

    /**
     * Ile dni zostało tym pracownikom w jednym wskazanym kraju — do listy
     * wyboru przy wysyłaniu ludzi na budowę.
     *
     * @param  array<int, int>  $pracownicy
     * @return array<int, array{dni_12m: int, pozostalo: int, status: string}>
     */
    public function dlaKraju(array $pracownicy, ?string $kraj, ?string $dzien = null): array
    {
        if ($kraj === null || $pracownicy === []) {
            return [];
        }

        $dzis = Carbon::parse($dzien ?? Carbon::today()->toDateString())->startOfDay();
        $kalendarze = $this->kalendarzeDlaPracownikow($pracownicy, $dzis);
        $wynik = [];

        foreach ($pracownicy as $id) {
            $dni = $kalendarze[$id][$kraj] ?? [];
            ksort($dni);
            $wykorzystane = $this->wOknieDo(array_keys($dni), $dzis);
            $pozostalo = max(0, self::LIMIT_DNI - $wykorzystane);

            $wynik[$id] = [
                'dni_12m' => $wykorzystane,
                'pozostalo' => $pozostalo,
                'status' => $this->status($pozostalo),
            ];
        }

        return $wynik;
    }

    /**
     * Kalendarze pobytów wielu pracowników naraz.
     *
     * @param  array<int, int>  $pracownicy
     * @return array<int, array<string, array<string, true>>>
     */
    private function kalendarzeDlaPracownikow(array $pracownicy, Carbon $dzis): array
    {
        $macierzyste = KrajTyp::idsBezA1();

        $pobyty = ContactWorkDate::with('organization.krajTyp')
            ->whereIn('contact_id', $pracownicy ?: [0])
            ->whereNotNull('start')
            ->get();

        $kalendarze = [];

        foreach ($pobyty as $pobyt) {
            $kraj = optional(optional($pobyt->organization)->krajTyp);

            if (! $kraj->name || in_array((int) $kraj->id, $macierzyste, true)) {
                continue;
            }

            foreach ($this->dniPobytu($pobyt, $dzis) as $dzien) {
                $kalendarze[$pobyt->contact_id][$kraj->name][$dzien] = true;
            }
        }

        return $kalendarze;
    }

    private function status(int $pozostalo): string
    {
        if ($pozostalo === 0) {
            return 'wyczerpany';
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

            foreach ($this->dniPobytu($pobyt, $dzis) as $dzien) {
                $kalendarz[$kraj->name][$dzien] = true;
            }
        }

        return $kalendarz;
    }

    /**
     * Dni jednego pobytu, razem z przyjazdem i wyjazdem. Pobyt bez daty końca
     * wciąż trwa; przyszłości nie liczymy jako dni już wykorzystanych.
     *
     * @return array<int, string>
     */
    private function dniPobytu(ContactWorkDate $pobyt, Carbon $dzis): array
    {
        $od = Carbon::parse($pobyt->start)->startOfDay();
        $do = $pobyt->end ? Carbon::parse($pobyt->end)->startOfDay() : $dzis->copy();

        if ($do->gt($dzis)) {
            $do = $dzis->copy();
        }

        if ($do->lt($od)) {
            return [];
        }

        $dni = [];

        for ($dzien = $od->copy(); $dzien->lte($do); $dzien->addDay()) {
            $dni[] = $dzien->toDateString();
        }

        return $dni;
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
