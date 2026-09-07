<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kierownictwo firmy — nowa rola (owner = 4). Na razie ma dokładnie ten sam
 * zakres co biuro; osobna rola istnieje po to, żeby dało się je później
 * rozdzielić bez ruszania kont. Nie mylić z kierownikiem budowy (owner = 3).
 */
class RolaKierownictwoTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
    }

    private function uzytkownik(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => $owner,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_kierownictwo_ma_uprawnienia_biurowe(): void
    {
        $u = $this->uzytkownik(Role::KIEROWNICTWO->value, 'zarzad@mkl.pl');

        $this->assertTrue($u->isKierownictwo());
        $this->assertTrue($u->isOffice(), 'Kierownictwo ma widzieć wszystkie budowy, jak biuro.');
        $this->assertFalse($u->isAdmin());
        $this->assertFalse($u->isKierownik(), 'To nie jest kierownik budowy.');
    }

    public function test_widoki_dostaja_flage_biura_i_wlasna(): void
    {
        $u = $this->uzytkownik(Role::KIEROWNICTWO->value, 'zarzad@mkl.pl');
        $p = $u->permissions;

        // "biuro" w widokach znaczy "uprawnienia biurowe" — stąd true.
        $this->assertTrue($p['biuro']);
        $this->assertTrue($p['kierownictwo']);
        $this->assertFalse($p['admin']);
        $this->assertFalse($p['kierownik']);
    }

    public function test_biuro_nie_dostaje_flagi_kierownictwa(): void
    {
        $p = $this->uzytkownik(Role::BIURO->value, 'biuro@mkl.pl')->permissions;

        $this->assertTrue($p['biuro']);
        $this->assertFalse($p['kierownictwo'], 'Biuro to nadal biuro — flagi się nie mieszają.');
    }

    /** @dataProvider trasyBiurowe */
    public function test_kierownictwo_wchodzi_tam_gdzie_biuro(string $trasa): void
    {
        $kierownictwo = $this->uzytkownik(Role::KIEROWNICTWO->value, 'zarzad@mkl.pl');
        $biuro = $this->uzytkownik(Role::BIURO->value, 'biuro@mkl.pl');

        $oczekiwany = $this->actingAs($biuro)->get($trasa)->getStatusCode();

        $this->actingAs($kierownictwo)->get($trasa)->assertStatus($oczekiwany);
    }

    /** @return array<string, array{string}> */
    public function trasyBiurowe(): array
    {
        return [
            'pracownicy' => ['/contacts'],
            'budowy' => ['/budowy'],
            'sprzet' => ['/narzedzia'],
            'zmiany kadrowe' => ['/zmiany-kadrowe'],
            'raport terminow' => ['/reports/koniecUprawinien'],
            'ustawienia' => ['/tools'],
            'uzytkownicy' => ['/users'],
            'pulpit' => ['/'],
        ];
    }

    /** @dataProvider trasyAdmina */
    public function test_kierownictwo_nie_wchodzi_tam_gdzie_tylko_admin(string $trasa): void
    {
        $u = $this->uzytkownik(Role::KIEROWNICTWO->value, 'zarzad@mkl.pl');

        $this->actingAs($u)->get($trasa)->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public function trasyAdmina(): array
    {
        return [
            'rejestr logowan' => ['/logowania'],
            'badania typ' => ['/badaniaTyp'],
            'nowy artykul' => ['/baza-wiedzy/create'],
        ];
    }

    public function test_kierownik_budowy_dalej_nie_wchodzi_do_biurowych(): void
    {
        $u = $this->uzytkownik(Role::KIEROWNIK->value, 'kierownik@mkl.pl');

        $this->actingAs($u)->get('/contacts')->assertForbidden();
        $this->actingAs($u)->get('/zmiany-kadrowe')->assertForbidden();
    }

    public function test_mozna_zalozyc_konto_z_rola_kierownictwo(): void
    {
        $admin = $this->uzytkownik(Role::ADMIN->value, 'admin@mkl.pl');

        $this->actingAs($admin)->post('/users', [
            'first_name' => 'Anna',
            'last_name' => 'Zarzadowa',
            'email' => 'anna.zarzadowa@mkl.pl',
            'owner' => Role::KIEROWNICTWO->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'anna.zarzadowa@mkl.pl',
            'owner' => Role::KIEROWNICTWO->value,
        ]);
    }

    public function test_nieznana_rola_jest_odrzucana(): void
    {
        $admin = $this->uzytkownik(Role::ADMIN->value, 'admin@mkl.pl');

        // Dotąd walidacja przepuszczała dowolną liczbę do 10 znaków.
        $this->actingAs($admin)->post('/users', [
            'first_name' => 'Ktos',
            'last_name' => 'Obcy',
            'email' => 'ktos.obcy@mkl.pl',
            'owner' => 99,
        ])->assertSessionHasErrors('owner');

        $this->assertDatabaseMissing('users', ['email' => 'ktos.obcy@mkl.pl']);
    }

    public function test_lista_rol_zawiera_kierownictwo(): void
    {
        $this->assertSame([1, 2, 3, 4], Role::values());
        $this->assertContains(Role::KIEROWNICTWO->value, Role::officeValues());
        $this->assertNotContains(Role::KIEROWNIK->value, Role::officeValues());
        $this->assertSame('Kierownictwo', Role::KIEROWNICTWO->label());
    }
}
