<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Nazwane uprawnienia — to, o co pytają trasy (`moze:kartoteki.edycja`),
 * menu i przyciski. Kto które ma, mówi macierz w App\Uprawnienia\Macierz;
 * tu jest tylko lista.
 *
 * Zawężenie "tylko swoje budowy i ludzie" to osobna warstwa (polityki
 * i sprawdzenie zakresu w middleware Moze) — uprawnienie mówi CZY rola
 * wchodzi w dany obszar, zakres mówi DO CZEGO w nim.
 */
enum Uprawnienie: string
{
    case BUDOWY_PODGLAD = 'budowy.podglad';
    case BUDOWY_ZAKLADANIE = 'budowy.zakladanie';
    case BUDOWY_EDYCJA = 'budowy.edycja';
    case BUDOWY_ARCHIWIZACJA = 'budowy.archiwizacja';
    case BUDOWY_PRZYPISYWANIE = 'budowy.przypisywanie';

    /** Karta jednego pracownika — kierownik ma ją dla swoich ludzi. */
    case KARTOTEKI_PODGLAD = 'kartoteki.podglad';
    /** Lista wszystkich pracowników i kierowników — tego kierownik nie ma. */
    case KARTOTEKI_LISTA = 'kartoteki.lista';
    case KARTOTEKI_EDYCJA = 'kartoteki.edycja';

    /** Badania, uprawnienia, BHP, A1, certyfikaty KJ, dokumenty. */
    case DOKUMENTY_PODGLAD = 'dokumenty.podglad';
    case DOKUMENTY_DODAWANIE = 'dokumenty.dodawanie';
    case DOKUMENTY_EDYCJA = 'dokumenty.edycja';
    case DOKUMENTY_USUWANIE = 'dokumenty.usuwanie';

    case NIEOBECNOSCI_PODGLAD = 'nieobecnosci.podglad';
    case NIEOBECNOSCI_DODAWANIE = 'nieobecnosci.dodawanie';
    case NIEOBECNOSCI_EDYCJA = 'nieobecnosci.edycja';
    case NIEOBECNOSCI_USUWANIE = 'nieobecnosci.usuwanie';

    /** KCP budowy: podgląd, wpisywanie, eksport. Zakres budów z warstwy zakresu. */
    case KCP_WPISYWANIE = 'kcp.wpisywanie';
    /** Raport miesięczny i zbiorczy ze wszystkich budów. */
    case KCP_RAPORTY = 'kcp.raporty';

    case SPRZET_PODGLAD = 'sprzet.podglad';
    case SPRZET_OBSLUGA = 'sprzet.obsluga';

    case ZMIANY_KADROWE = 'zmiany_kadrowe.obsluga';
    /** Kierownik zgłasza kadrom zjazd, urlop, przeniesienie — sam nic nie zmienia. */
    case ZGLOSZENIA_WYSYLANIE = 'zgloszenia.wysylanie';

    case PROGNOZA_PODGLAD = 'prognoza.podglad';
    case PROGNOZA_OBSLUGA = 'prognoza.obsluga';

    case RAPORT_TERMINOW = 'raport_terminow.podglad';
    case STATYSTYKI = 'statystyki.podglad';

    case UZYTKOWNICY_LISTA = 'uzytkownicy.lista';
    case UZYTKOWNICY_ZAKLADANIE = 'uzytkownicy.zakladanie';
    /** Cudze konto; własny profil każdy edytuje bez uprawnienia. */
    case UZYTKOWNICY_EDYCJA = 'uzytkownicy.edycja';
    case UZYTKOWNICY_BLOKOWANIE = 'uzytkownicy.blokowanie';
    case UZYTKOWNICY_USUWANIE = 'uzytkownicy.usuwanie';
    case UZYTKOWNICY_WEJDZ_JAKO = 'uzytkownicy.wejdz_jako';

    /** Stanowiska, godziny pracy, typy uprawnień i sprzętu, grupy, wykres prognozy. */
    case SLOWNIKI_BIURA = 'slowniki.biura';
    /** Typy badań, BHP, dokumentów, języki, kraje, święta. */
    case SLOWNIKI_SYSTEMOWE = 'slowniki.systemowe';

    case BAZA_WIEDZY_PISANIE = 'baza_wiedzy.pisanie';
    case REJESTR_LOGOWAN = 'rejestr_logowan.podglad';
    /** Ekran Ustawienia → Uprawnienia ról. Tylko admin, nie do nadania. */
    case UPRAWNIENIA_ZARZADZANIE = 'uprawnienia.zarzadzanie';

    /**
     * Uprawnienia, których ekran uprawnień nie pozwala nadać nikomu poza
     * adminem — inaczej dałoby się rozdać "Wejdź jako" albo sam ekran.
     */
    public function tylkoAdmin(): bool
    {
        return in_array($this, [
            self::UZYTKOWNICY_WEJDZ_JAKO,
            self::REJESTR_LOGOWAN,
            self::UPRAWNIENIA_ZARZADZANIE,
        ], true);
    }

    /**
     * Co dane uprawnienie zakłada: edycja bez podglądu nie ma sensu, a formularz
     * dokumentu siedzi w karcie pracownika. Ekran domyka to sam przy zapisie.
     *
     * @return self[]
     */
    public function wymaga(): array
    {
        return match ($this) {
            self::BUDOWY_ZAKLADANIE, self::BUDOWY_EDYCJA, self::BUDOWY_ARCHIWIZACJA => [self::BUDOWY_PODGLAD],
            self::BUDOWY_PRZYPISYWANIE => [self::BUDOWY_PODGLAD, self::KARTOTEKI_PODGLAD],
            self::KARTOTEKI_LISTA, self::KARTOTEKI_EDYCJA => [self::KARTOTEKI_PODGLAD],
            self::DOKUMENTY_PODGLAD => [self::KARTOTEKI_PODGLAD],
            self::DOKUMENTY_DODAWANIE, self::DOKUMENTY_EDYCJA, self::DOKUMENTY_USUWANIE => [self::DOKUMENTY_PODGLAD],
            self::NIEOBECNOSCI_PODGLAD => [self::KARTOTEKI_PODGLAD],
            self::NIEOBECNOSCI_DODAWANIE, self::NIEOBECNOSCI_EDYCJA, self::NIEOBECNOSCI_USUWANIE => [self::NIEOBECNOSCI_PODGLAD],
            self::KCP_WPISYWANIE, self::ZGLOSZENIA_WYSYLANIE => [self::BUDOWY_PODGLAD],
            self::SPRZET_OBSLUGA => [self::SPRZET_PODGLAD],
            self::PROGNOZA_OBSLUGA => [self::PROGNOZA_PODGLAD],
            self::UZYTKOWNICY_ZAKLADANIE, self::UZYTKOWNICY_EDYCJA, self::UZYTKOWNICY_BLOKOWANIE,
            self::UZYTKOWNICY_USUWANIE, self::UZYTKOWNICY_WEJDZ_JAKO => [self::UZYTKOWNICY_LISTA],
            default => [],
        };
    }

    public function obszar(): string
    {
        return match (explode('.', $this->value)[0]) {
            'budowy' => 'Budowy',
            'kartoteki' => 'Kartoteki pracowników',
            'dokumenty' => 'Badania, uprawnienia, BHP, A1, dokumenty',
            'nieobecnosci' => 'Nieobecności',
            'kcp' => 'Karty pracy (KCP)',
            'sprzet' => 'Sprzęt',
            'zmiany_kadrowe' => 'Kadry',
            'zgloszenia' => 'Zgłoszenia do kadr',
            'prognoza' => 'Prognoza pracowników',
            'raport_terminow' => 'Raport terminów uprawnień',
            'statystyki' => 'Statystyki',
            'uzytkownicy' => 'Użytkownicy',
            'slowniki' => 'Ustawienia i słowniki',
            'baza_wiedzy' => 'Baza wiedzy',
            'rejestr_logowan' => 'Rejestr logowań',
            'uprawnienia' => 'Uprawnienia ról',
        };
    }

    public function etykieta(): string
    {
        return match ($this) {
            self::BUDOWY_PODGLAD => 'podgląd',
            self::BUDOWY_ZAKLADANIE => 'zakładanie',
            self::BUDOWY_EDYCJA => 'edycja (w tym dane klienta)',
            self::BUDOWY_ARCHIWIZACJA => 'archiwizacja i przywracanie',
            self::BUDOWY_PRZYPISYWANIE => 'przypisywanie ludzi i kierownictwa',
            self::KARTOTEKI_PODGLAD => 'podgląd karty pracownika',
            self::KARTOTEKI_LISTA => 'lista wszystkich pracowników i kierowników',
            self::KARTOTEKI_EDYCJA => 'dodawanie, edycja, usuwanie, umowy',
            self::DOKUMENTY_PODGLAD => 'podgląd',
            self::DOKUMENTY_DODAWANIE => 'dodawanie',
            self::DOKUMENTY_EDYCJA => 'edycja',
            self::DOKUMENTY_USUWANIE => 'usuwanie i przywracanie',
            self::NIEOBECNOSCI_PODGLAD => 'podgląd',
            self::NIEOBECNOSCI_DODAWANIE => 'dodawanie',
            self::NIEOBECNOSCI_EDYCJA => 'edycja',
            self::NIEOBECNOSCI_USUWANIE => 'usuwanie i przywracanie',
            self::KCP_WPISYWANIE => 'podgląd, wpisywanie, eksport',
            self::KCP_RAPORTY => 'raport miesięczny i zbiorczy',
            self::SPRZET_PODGLAD => 'podgląd sprzętu na budowie',
            self::SPRZET_OBSLUGA => 'magazyn, przypisania, dokumenty',
            self::ZMIANY_KADROWE => 'obsługa zmian pobytów i zgłoszeń od kierowników',
            self::ZGLOSZENIA_WYSYLANIE => 'wysyłanie zgłoszeń o zjeździe, urlopie, przeniesieniu',
            self::PROGNOZA_PODGLAD => 'podgląd',
            self::PROGNOZA_OBSLUGA => 'obsługa',
            self::RAPORT_TERMINOW => 'podgląd',
            self::STATYSTYKI => 'podgląd',
            self::UZYTKOWNICY_LISTA => 'lista',
            self::UZYTKOWNICY_ZAKLADANIE => 'zakładanie',
            self::UZYTKOWNICY_EDYCJA => 'edycja cudzych kont',
            self::UZYTKOWNICY_BLOKOWANIE => 'blokowanie i odblokowanie',
            self::UZYTKOWNICY_USUWANIE => 'usuwanie i przywracanie',
            self::UZYTKOWNICY_WEJDZ_JAKO => '„Wejdź jako"',
            self::SLOWNIKI_BIURA => 'słowniki biura: stanowiska, godziny pracy, typy uprawnień i sprzętu, grupy, wykres prognozy',
            self::SLOWNIKI_SYSTEMOWE => 'słowniki systemowe: typy badań, BHP, dokumentów, języki, kraje, święta',
            self::BAZA_WIEDZY_PISANIE => 'pisanie artykułów',
            self::REJESTR_LOGOWAN => 'podgląd',
            self::UPRAWNIENIA_ZARZADZANIE => 'ekran uprawnień ról',
        };
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn (self $u) => $u->value, self::cases());
    }
}
