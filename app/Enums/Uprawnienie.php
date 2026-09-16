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

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn (self $u) => $u->value, self::cases());
    }
}
