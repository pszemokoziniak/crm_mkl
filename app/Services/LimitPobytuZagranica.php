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
 * Sposób liczenia nie jest wszędzie taki sam i siedzi przy kraju w słowniku:
 *
 *  - rok podatkowy (Austria, Francja, Hiszpania, Luksemburg, Włochy) — liczy
 *    się rok kalendarzowy, 1 stycznia licznik rusza od zera;
 *  - każde 12 miesięcy (Niemcy, Belgia, Dania, Holandia, Portugalia, Szwecja
 *    i reszta) — liczy się KAŻDY kolejny okres 365 dni, w przód od przyjazdu
 *    i wstecz od wyjazdu, więc pobyty z dwóch różnych lat sumują się.
 *
 * Liczymy całe pobyty, razem z dniem przyjazdu i wyjazdu. System nie wie,
 * czy ktoś wracał do domu na weekendy, więc wynik jest GÓRNĄ GRANICĄ:
 * nadaje się na ostrzeżenie, nie zastępuje wyliczenia księgowości.
 *
 * Budowa oznaczona jako zakład podatkowy w ogóle tu nie wchodzi — tam
 * podatek należy się od pierwszego dnia i próg nie ma znaczenia.
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
        [$kalendarz, $sposoby] = $this->kalendarzPracownika($contact, $dzis);

        $wiersze = [];

        foreach ($kalendarz as $kraj => $dni) {
            ksort($dni);
            $lista = array_keys($dni);
            $sposob = $sposoby[$kraj];

            $wykorzystane = $this->wOknie($lista, $dzis, $sposob);
            $pozostalo = max(0, self::LIMIT_DNI - $wykorzystane);
            [$najwieksze, $od, $do] = $this->najwiekszeOkno($lista, $sposob);

            $wiersze[] = [
                'kraj' => $kraj,
                'sposob' => $sposob,
                'okres' => $sposob === KrajTyp::SPOSOB_ROK
                    ? 'rok '.$dzis->year
                    : 'ostatnie 12 mies.',
                'dni_12m' => $wykorzystane,
                'pozostalo' => $pozostalo,
                'najwieksze_okno' => $najwieksze,
                'okno_od' => $od,
                'okno_do' => $do,
                'kiedys_przekroczony' => $najwieksze > self::LIMIT_DNI,
                'wolne_od' => $pozostalo === 0 ? $this->kiedyZwolniSie($lista, $dzis, $sposob) : null,
                'status' => $this->status($pozostalo),
            ];
        }

        usort($wiersze, fn ($a, $b) => $b['dni_12m'] <=> $a['dni_12m']);

        return $wiersze;
    }

    /**
     * Czy dopisanie pobytu przepełni limit w kraju tej budowy.
     *
     * Liczymy z dniami zaplanowanymi na przyszłość, bo pytanie brzmi "czy
     * wolno go tam wysłać", a nie "ile już wykorzystał". Zwraca null, gdy
     * jest w porządku, gdy budowa jest w kraju macierzystym albo gdy to
     * zakład podatkowy.
     *
     * @return array{kraj: string, dni: int, od: ?string, do: ?string, sposob: string}|null
     */
    public function przekroczenieDla(Contact $contact, Organization $budowa, string $od, string $do): ?array
    {
        // Budowa bez rozpoznanego kraju wymaga A1 (ostrożnie), ale limitu
        // nie ma jak liczyć — nie wiadomo, którego państwa miałby dotyczyć.
        if (! $budowa->liczySieDoLimitu183() || ! $budowa->krajTyp) {
            return null;
        }

        $kraj = $budowa->krajTyp;
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
        $sposob = $this->sposob($kraj);
        [$najwieksze, $oknoOd, $oknoDo] = $this->najwiekszeOkno(array_keys($dni), $sposob);

        if ($najwieksze <= self::LIMIT_DNI) {
            return null;
        }

        return [
            'kraj' => $kraj->name,
            'dni' => $najwieksze,
            'od' => $oknoOd,
            'do' => $oknoDo,
            'sposob' => $sposob,
        ];
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

        $naBudowie = ContactWorkDate::with('organization.krajTyp', 'contact')
            ->whereHas('contact')
            ->when($tylkoPracownicy !== null, fn ($q) => $q->whereIn('contact_id', $tylkoPracownicy ?: [0]))
            ->activeOn($dzis->toDateString())
            ->get()
            ->filter(fn (ContactWorkDate $pobyt) => $pobyt->organization
                && $pobyt->organization->krajTyp
                && $pobyt->organization->liczySieDoLimitu183());

        if ($naBudowie->isEmpty()) {
            return [];
        }

        [$kalendarze, $sposoby] = $this->kalendarzeDlaPracownikow(
            $naBudowie->pluck('contact_id')->unique()->all(),
            $dzis
        );

        $wiersze = [];

        foreach ($naBudowie as $pobyt) {
            $kraj = $pobyt->organization->krajTyp->name;
            $dni = $kalendarze[$pobyt->contact_id][$kraj] ?? [];

            if ($dni === []) {
                continue;
            }

            ksort($dni);
            $sposob = $sposoby[$kraj] ?? KrajTyp::SPOSOB_12M;
            $wykorzystane = $this->wOknie(array_keys($dni), $dzis, $sposob);
            $pozostalo = max(0, self::LIMIT_DNI - $wykorzystane);

            if ($pozostalo > $prog) {
                continue;
            }

            // Dzień, w którym limit pęknie, jeśli pobyt potrwa bez przerwy.
            $przekroczy = $dzis->copy()->addDays($pozostalo);

            // Przy liczeniu rocznym 1 stycznia licznik rusza od zera, więc
            // termin przypadający na przyszły rok w ogóle nie nadejdzie.
            if ($sposob === KrajTyp::SPOSOB_ROK && $przekroczy->year > $dzis->year) {
                continue;
            }

            $wiersze[$pobyt->contact_id.'-'.$kraj] = [
                'contact' => $pobyt->contact,
                'organization' => $pobyt->organization,
                'kraj' => $kraj,
                'sposob' => $sposob,
                'dni_12m' => $wykorzystane,
                'pozostalo' => $pozostalo,
                'przekroczy' => $przekroczy->toDateString(),
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
        [$kalendarze, $sposoby] = $this->kalendarzeDlaPracownikow($pracownicy, $dzis);
        $sposob = $sposoby[$kraj] ?? KrajTyp::SPOSOB_12M;
        $wynik = [];

        foreach ($pracownicy as $id) {
            $dni = $kalendarze[$id][$kraj] ?? [];
            ksort($dni);
            $wykorzystane = $this->wOknie(array_keys($dni), $dzis, $sposob);
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
     * Stan mówi o dziś, nie o historii. Kto przekroczył próg dwa lata temu,
     * a teraz ma zapas 53 dni, może jechać — czerwień przy jego nazwisku
     * wstrzymywałaby wyjazd bez powodu. Dawne przekroczenie pokazujemy
     * osobno, przy najdłuższym oknie.
     */
    private function status(int $pozostalo): string
    {
        if ($pozostalo === 0) {
            return 'wyczerpany';
        }

        return $pozostalo <= self::PROG_UWAGI ? 'uwaga' : 'ok';
    }

    private function sposob(?KrajTyp $kraj): string
    {
        return $kraj && $kraj->sposob_183 ? $kraj->sposob_183 : KrajTyp::SPOSOB_12M;
    }

    /**
     * @return array{0: array<string, array<string, true>>, 1: array<string, string>}
     */
    private function kalendarzPracownika(Contact $contact, Carbon $dzis): array
    {
        [$kalendarze, $sposoby] = $this->kalendarzeDlaPracownikow([$contact->id], $dzis);

        return [$kalendarze[$contact->id] ?? [], $sposoby];
    }

    /**
     * Kalendarze pobytów wielu pracowników naraz, bez powtórzeń — dwie budowy
     * w tym samym kraju to wciąż jeden dzień za granicą.
     *
     * Kraj macierzysty poznajemy po tym samym znaczniku, co przy A1: gdzie A1
     * nie jest potrzebne, tam jesteśmy u siebie i limit nie biegnie.
     *
     * @param  array<int, int>  $pracownicy
     * @return array{0: array<int, array<string, array<string, true>>>, 1: array<string, string>}
     */
    private function kalendarzeDlaPracownikow(array $pracownicy, Carbon $dzis): array
    {
        $pobyty = ContactWorkDate::with('organization.krajTyp')
            ->whereIn('contact_id', $pracownicy ?: [0])
            ->whereNotNull('start')
            ->get();

        $kalendarze = [];
        $sposoby = [];

        foreach ($pobyty as $pobyt) {
            if (! $pobyt->organization
                || ! $pobyt->organization->krajTyp
                || ! $pobyt->organization->liczySieDoLimitu183()) {
                continue;
            }

            $kraj = $pobyt->organization->krajTyp;
            $sposoby[$kraj->name] = $this->sposob($kraj);

            foreach ($this->dniPobytu($pobyt, $dzis) as $dzien) {
                $kalendarze[$pobyt->contact_id][$kraj->name][$dzien] = true;
            }
        }

        return [$kalendarze, $sposoby];
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

    /** Ile dni wchodzi do okresu rozliczeniowego kończącego się danego dnia. */
    private function wOknie(array $lista, Carbon $koniec, string $sposob): int
    {
        $poczatek = $sposob === KrajTyp::SPOSOB_ROK
            ? $koniec->copy()->startOfYear()->toDateString()
            : $koniec->copy()->subDays(self::OKNO_DNI - 1)->toDateString();

        $koniecTekst = $koniec->toDateString();

        return count(array_filter(
            $lista,
            fn (string $dzien) => $dzien >= $poczatek && $dzien <= $koniecTekst
        ));
    }

    /**
     * Najgorszy okres: przy liczeniu rocznym najcięższy rok kalendarzowy,
     * przy dwunastomiesięcznym najcięższe dowolne kolejne 365 dni.
     *
     * @return array{0: int, 1: ?string, 2: ?string}
     */
    private function najwiekszeOkno(array $lista, string $sposob): array
    {
        if ($lista === []) {
            return [0, null, null];
        }

        if ($sposob === KrajTyp::SPOSOB_ROK) {
            $poLatach = [];

            foreach ($lista as $dzien) {
                $poLatach[substr($dzien, 0, 4)][] = $dzien;
            }

            $najgorszy = [];
            foreach ($poLatach as $dni) {
                if (count($dni) > count($najgorszy)) {
                    $najgorszy = $dni;
                }
            }

            return [count($najgorszy), reset($najgorszy), end($najgorszy)];
        }

        $ile = count($lista);
        $najlepsze = 0;
        $od = null;
        $do = null;
        $lewy = 0;

        for ($prawy = 0; $prawy < $ile; $prawy++) {
            while ((int) Carbon::parse($lista[$lewy])->diffInDays(Carbon::parse($lista[$prawy]), true) >= self::OKNO_DNI) {
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
     * Kiedy limit sam się poluzuje: przy liczeniu rocznym 1 stycznia, przy
     * dwunastomiesięcznym wtedy, gdy z okna wypadnie dość starych dni.
     */
    private function kiedyZwolniSie(array $lista, Carbon $dzis, string $sposob): ?string
    {
        if ($sposob === KrajTyp::SPOSOB_ROK) {
            return $dzis->copy()->addYear()->startOfYear()->toDateString();
        }

        $dzien = $dzis->copy();
        $granica = $dzis->copy()->addDays(self::OKNO_DNI);

        while ($dzien->lte($granica)) {
            if ($this->wOknie($lista, $dzien, $sposob) < self::LIMIT_DNI) {
                return $dzien->toDateString();
            }

            $dzien->addDay();
        }

        return null;
    }

    /** @return \Illuminate\Support\Collection<int, ContactWorkDate> */
    private function pobytyWKraju(Contact $contact, string $kraj)
    {
        return ContactWorkDate::with('organization.krajTyp')
            ->where('contact_id', $contact->id)
            ->whereNotNull('start')
            ->get()
            ->filter(fn (ContactWorkDate $p) => $p->organization
                && $p->organization->liczySieDoLimitu183()
                && optional($p->organization->krajTyp)->name === $kraj);
    }
}
