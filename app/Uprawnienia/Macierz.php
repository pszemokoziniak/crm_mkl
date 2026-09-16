<?php

declare(strict_types=1);

namespace App\Uprawnienia;

use App\Enums\Role;
use App\Enums\Uprawnienie;

/**
 * Kto co może: rola → lista uprawnień. Jedyne miejsce, gdzie zakres roli
 * jest zapisany; trasy i widoki pytają `User::moze()`, nie numerów ról.
 *
 * Dziś zakresy są dokładnie takie, jakie były przed wprowadzeniem tej
 * warstwy (biuro, kadry i kierownictwo — to samo; kierownik budowy
 * i kierownik projektu — to samo). Zmiana zakresu roli to zmiana jednej
 * listy tutaj. Admin ma zawsze wszystko i nie da się mu tego odebrać —
 * inaczej ekran uprawnień mógłby zamknąć drzwi samemu sobie.
 */
final class Macierz
{
    /**
     * @return Uprawnienie[]
     */
    public static function dla(Role $rola): array
    {
        if ($rola === Role::ADMIN) {
            return Uprawnienie::cases();
        }

        return self::domyslne()[$rola->value] ?? [];
    }

    public static function ma(Role $rola, Uprawnienie $uprawnienie): bool
    {
        return in_array($uprawnienie, self::dla($rola), true);
    }

    /**
     * @return array<int, Uprawnienie[]>
     */
    private static function domyslne(): array
    {
        $kierownik = [
            Uprawnienie::BUDOWY_PODGLAD,
            Uprawnienie::KARTOTEKI_PODGLAD,
            Uprawnienie::DOKUMENTY_PODGLAD,
            Uprawnienie::NIEOBECNOSCI_PODGLAD,
            Uprawnienie::KCP_WPISYWANIE,
            Uprawnienie::SPRZET_PODGLAD,
            Uprawnienie::PROGNOZA_PODGLAD,
            Uprawnienie::RAPORT_TERMINOW,
            Uprawnienie::STATYSTYKI,
        ];

        // Biuro: wszystko poza tym, co należy do administratora systemu.
        $biuro = array_values(array_filter(
            Uprawnienie::cases(),
            fn (Uprawnienie $u) => ! in_array($u, [
                Uprawnienie::SLOWNIKI_SYSTEMOWE,
                Uprawnienie::BAZA_WIEDZY_PISANIE,
                Uprawnienie::REJESTR_LOGOWAN,
                Uprawnienie::UZYTKOWNICY_WEJDZ_JAKO,
            ], true),
        ));

        return [
            Role::BIURO->value => $biuro,
            Role::KIEROWNICTWO->value => $biuro,
            Role::KADRY->value => $biuro,
            Role::KIEROWNIK->value => $kierownik,
            Role::KIEROWNIK_PROJEKTU->value => $kierownik,
        ];
    }
}
