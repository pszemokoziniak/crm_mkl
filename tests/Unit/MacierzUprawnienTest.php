<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Role;
use App\Enums\Uprawnienie;
use App\Uprawnienia\Macierz;
use PHPUnit\Framework\TestCase;

/**
 * Macierz "kto co może". Dzisiejsze zakresy: biuro, kadry i kierownictwo
 * mają to samo; kierownik budowy i kierownik projektu mają to samo;
 * admin ma wszystko i nie da się mu tego odebrać.
 */
class MacierzUprawnienTest extends TestCase
{
    public function test_admin_ma_kazde_uprawnienie(): void
    {
        foreach (Uprawnienie::cases() as $u) {
            $this->assertTrue(Macierz::ma(Role::ADMIN, $u), $u->value);
        }
    }

    public function test_kadry_i_kierownictwo_maja_dokladnie_zakres_biura(): void
    {
        $this->assertSame(Macierz::dla(Role::BIURO), Macierz::dla(Role::KADRY));
        $this->assertSame(Macierz::dla(Role::BIURO), Macierz::dla(Role::KIEROWNICTWO));
    }

    public function test_kierownik_projektu_ma_dokladnie_zakres_kierownika_budowy(): void
    {
        $this->assertSame(Macierz::dla(Role::KIEROWNIK), Macierz::dla(Role::KIEROWNIK_PROJEKTU));
    }

    public function test_biuru_brakuje_tylko_tego_co_nalezy_do_admina(): void
    {
        $brak = array_values(array_filter(
            Uprawnienie::cases(),
            fn (Uprawnienie $u) => ! Macierz::ma(Role::BIURO, $u),
        ));

        $this->assertSame([
            Uprawnienie::UZYTKOWNICY_WEJDZ_JAKO,
            Uprawnienie::SLOWNIKI_SYSTEMOWE,
            Uprawnienie::BAZA_WIEDZY_PISANIE,
            Uprawnienie::REJESTR_LOGOWAN,
        ], $brak);
    }

    public function test_kierownik_tylko_oglada_i_wpisuje_kcp(): void
    {
        $ma = Macierz::dla(Role::KIEROWNIK);

        $this->assertContains(Uprawnienie::KCP_WPISYWANIE, $ma);
        $this->assertContains(Uprawnienie::KARTOTEKI_PODGLAD, $ma);
        $this->assertNotContains(Uprawnienie::KARTOTEKI_LISTA, $ma);
        $this->assertNotContains(Uprawnienie::KARTOTEKI_EDYCJA, $ma);
        $this->assertNotContains(Uprawnienie::DOKUMENTY_DODAWANIE, $ma);
        $this->assertNotContains(Uprawnienie::KCP_RAPORTY, $ma);
        $this->assertNotContains(Uprawnienie::UZYTKOWNICY_LISTA, $ma);

        foreach ($ma as $u) {
            $this->assertTrue(
                str_ends_with($u->value, '.podglad') || $u === Uprawnienie::KCP_WPISYWANIE,
                'Kierownik dostał coś poza podglądem: '.$u->value,
            );
        }
    }
}
