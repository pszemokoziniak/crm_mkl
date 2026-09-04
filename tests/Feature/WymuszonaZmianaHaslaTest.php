<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wymuszona zmiana hasła (po 90 dniach albo gdy hasła nigdy nie zmieniano).
 * Ekran zmiany musi mieć wyjście — inaczej użytkownik zostaje w nim
 * zamknięty: "/" i "/login" odsyłają go z powrotem.
 */
class WymuszonaZmianaHaslaTest extends TestCase
{
    use RefreshDatabase;

    private function uzytkownik(?string $zmianaHasla): User
    {
        return User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => $zmianaHasla,
        ]);
    }

    public function test_konto_bez_zmiany_hasla_trafia_na_ekran_zmiany(): void
    {
        $this->actingAs($this->uzytkownik(null))
            ->get('/')
            ->assertRedirect(route('password.expired'));
    }

    public function test_haslo_starsze_niz_limit_trafia_na_ekran_zmiany(): void
    {
        $stare = now()->subDays((int) config('auth.password_expires_days') + 1)->toDateTimeString();

        $this->actingAs($this->uzytkownik($stare))
            ->get('/')
            ->assertRedirect(route('password.expired'));
    }

    public function test_swieze_haslo_wpuszcza_do_systemu(): void
    {
        $this->actingAs($this->uzytkownik(now()->toDateTimeString()))
            ->get('/')
            ->assertOk();
    }

    public function test_z_ekranu_zmiany_hasla_da_sie_wylogowac(): void
    {
        $uzytkownik = $this->uzytkownik(null);

        // Bez wylogowania to pułapka: logowanie odsyła na stronę główną,
        // a ta z powrotem na ekran zmiany hasła.
        $this->actingAs($uzytkownik)->get('/login')->assertRedirect('/');

        $this->actingAs($uzytkownik)->delete('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->get('/login')->assertOk();
    }

    public function test_zmiana_hasla_odblokowuje_konto(): void
    {
        $uzytkownik = $this->uzytkownik(null);

        $this->actingAs($uzytkownik)
            ->post('/password/expired', ['password' => 'NoweHaslo123!'])
            ->assertRedirect('/');

        $this->assertNotNull($uzytkownik->fresh()->password_changed_at);

        $this->actingAs($uzytkownik->fresh())->get('/')->assertOk();
    }
}
