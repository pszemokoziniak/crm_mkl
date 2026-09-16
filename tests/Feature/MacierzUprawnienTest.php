<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\Uprawnienie;
use App\Uprawnienia\Macierz;
use App\Models\Account;
use App\Models\UprawnieniaRoli;
use App\Models\User;
use App\Models\ZmianaUprawnien;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Macierz "kto co może". Dzisiejsze zakresy: biuro, kadry i kierownictwo
 * mają to samo; kierownik budowy i kierownik projektu mają to samo;
 * admin ma wszystko i nie da się mu tego odebrać.
 */
class MacierzUprawnienTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id, 'email' => 'admin@mkl.pl',
            'owner' => 1, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

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
            Uprawnienie::UPRAWNIENIA_ZARZADZANIE,
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

    public function test_nadpisanie_z_bazy_zastepuje_domyslne_tylko_tej_roli(): void
    {
        Macierz::zapisz(Role::BIURO, [Uprawnienie::BUDOWY_PODGLAD, Uprawnienie::SPRZET_OBSLUGA], $this->admin());

        $this->assertTrue(Macierz::jestNadpisana(Role::BIURO));
        $this->assertFalse(Macierz::jestNadpisana(Role::KADRY));
        $this->assertFalse(Macierz::ma(Role::BIURO, Uprawnienie::KARTOTEKI_LISTA));
        $this->assertTrue(Macierz::ma(Role::KADRY, Uprawnienie::KARTOTEKI_LISTA));
    }

    public function test_zapis_domyka_zaleznosci(): void
    {
        Macierz::zapisz(Role::BIURO, [Uprawnienie::DOKUMENTY_EDYCJA], $this->admin());

        $this->assertSame([
            Uprawnienie::KARTOTEKI_PODGLAD,
            Uprawnienie::DOKUMENTY_PODGLAD,
            Uprawnienie::DOKUMENTY_EDYCJA,
        ], Macierz::dla(Role::BIURO));
    }

    public function test_uprawnien_tylko_admina_nie_da_sie_nadac(): void
    {
        Macierz::zapisz(Role::KIEROWNICTWO, [Uprawnienie::UZYTKOWNICY_WEJDZ_JAKO, Uprawnienie::UPRAWNIENIA_ZARZADZANIE], $this->admin());

        $this->assertNotContains(Uprawnienie::UZYTKOWNICY_WEJDZ_JAKO, Macierz::dla(Role::KIEROWNICTWO));
        $this->assertNotContains(Uprawnienie::UPRAWNIENIA_ZARZADZANIE, Macierz::dla(Role::KIEROWNICTWO));
        // Zależność (lista użytkowników) została, samo "Wejdź jako" nie.
        $this->assertContains(Uprawnienie::UZYTKOWNICY_LISTA, Macierz::dla(Role::KIEROWNICTWO));
    }

    public function test_admina_nie_da_sie_ograniczyc(): void
    {
        Macierz::zapisz(Role::ADMIN, [], $this->admin());

        $this->assertSame(0, UprawnieniaRoli::count());
        $this->assertSame(Uprawnienie::cases(), Macierz::dla(Role::ADMIN));
    }

    public function test_dziennik_zapisuje_kto_co_dodal_i_odebral(): void
    {
        $admin = $this->admin();
        $bezListy = array_values(array_filter(Macierz::domyslneDla(Role::BIURO), fn ($u) => $u !== Uprawnienie::KARTOTEKI_LISTA));

        Macierz::zapisz(Role::BIURO, $bezListy, $admin);
        Macierz::zapisz(Role::BIURO, $bezListy, $admin); // bez zmiany — bez wpisu
        Macierz::przywrocDomyslne(Role::BIURO, $admin);

        $this->assertSame(2, ZmianaUprawnien::count());
        $pierwsza = ZmianaUprawnien::orderBy('id')->first();
        $this->assertSame($admin->id, (int) $pierwsza->user_id);
        $this->assertSame([], $pierwsza->dodane);
        $this->assertSame(['kartoteki.lista'], $pierwsza->odebrane);
        $ostatnia = ZmianaUprawnien::orderByDesc('id')->first();
        $this->assertTrue($ostatnia->przywrocenie);
        $this->assertSame(['kartoteki.lista'], $ostatnia->dodane);
        $this->assertFalse(Macierz::jestNadpisana(Role::BIURO));
    }
}
