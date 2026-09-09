<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rola "Kierownik projektu" — zakres taki sam jak kierownik budowy, ale jego
 * budowy biorą się z pola `organizations.kierownik_projektu_id`, a nie
 * z obecności w kierownictwie budowy.
 *
 * To rozróżnienie jest sednem: przy dosłownym "uprawnienia jak kierownik
 * budowy" opiekun kontraktu zalogowałby się i nie zobaczył ani jednej budowy,
 * bo nie ma wpisu w contact_work_dates ze stanowiskiem kierowniczym.
 */
class KierownikProjektuTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $opiekun;
    private Contact $opiekunKontakt;
    private Organization $mojaBudowa;
    private Organization $cudzaBudowa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $stanowisko = Funkcja::create([
            'name' => 'Kierownik Projektu',
            'kierownictwo' => true,
            'rola_budowy' => Funkcja::ROLA_KIEROWNIK_PROJEKTU,
        ]);

        $this->opiekun = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'opiekun@mkl.pl',
            'first_name' => 'Anna', 'last_name' => 'Opiekun',
            'owner' => Role::KIEROWNIK_PROJEKTU->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->opiekunKontakt = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Anna',
            'last_name' => 'Opiekun', 'funkcja_id' => $stanowisko->id,
            'user_id' => $this->opiekun->id,
        ]);

        $this->mojaBudowa = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Valmet Ortofta',
            'kierownik_projektu_id' => $this->opiekunKontakt->id,
        ]);

        $this->cudzaBudowa = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Berkes Lachendorf',
        ]);
    }

    private function pracownikNa(Organization $budowa, string $nazwisko): Contact
    {
        $osoba = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => $nazwisko,
        ]);

        ContactWorkDate::create([
            'contact_id' => $osoba->id, 'organization_id' => $budowa->id,
            'start' => now()->subMonth()->toDateString(), 'end' => null,
        ]);

        return $osoba;
    }

    public function test_rola_jest_do_wyboru_przy_zakladaniu_konta(): void
    {
        $this->assertContains(Role::KIEROWNIK_PROJEKTU->value, Role::values());
        $this->assertSame('Kierownik projektu', Role::KIEROWNIK_PROJEKTU->label());
    }

    public function test_nie_ma_uprawnien_biura(): void
    {
        // Zakres ma być jak u kierownika budowy, a nie jak u biura.
        $this->assertFalse($this->opiekun->isOffice());
        $this->assertTrue($this->opiekun->prowadziBudowy());
    }

    public function test_widzi_tylko_budowy_ktorych_jest_opiekunem(): void
    {
        $props = $this->actingAs($this->opiekun)->get('/budowy')->assertOk()->viewData('page')['props'];
        $nazwy = collect($props['organizations']['data'] ?? $props['organizations'])->pluck('nazwaBud');

        $this->assertContains('Valmet Ortofta', $nazwy);
        $this->assertNotContains('Berkes Lachendorf', $nazwy);
    }

    public function test_wchodzi_na_swoja_budowe_a_na_cudza_nie(): void
    {
        $this->actingAs($this->opiekun)->get("/budowy/{$this->mojaBudowa->id}/edit")->assertOk();
        $this->actingAs($this->opiekun)->get("/budowy/{$this->cudzaBudowa->id}/edit")->assertForbidden();
    }

    public function test_wchodzi_na_karte_swojego_pracownika_a_cudzego_nie(): void
    {
        $moj = $this->pracownikNa($this->mojaBudowa, 'Mojski');
        $obcy = $this->pracownikNa($this->cudzaBudowa, 'Obcy');

        $this->actingAs($this->opiekun)->get("/contacts/{$moj->id}/edit")->assertOk();
        $this->actingAs($this->opiekun)->get("/contacts/{$obcy->id}/edit")->assertForbidden();
    }

    public function test_pulpit_liczy_tylko_jego_budowy(): void
    {
        $this->pracownikNa($this->mojaBudowa, 'Mojski');
        $this->pracownikNa($this->cudzaBudowa, 'Obcy');

        $props = $this->actingAs($this->opiekun)->get('/')->assertOk()->viewData('page')['props'];

        $this->assertSame(1, $props['stats']['budowy']);
        $this->assertSame(1, $props['stats']['pracownicy']);
        $this->assertNull($props['stats']['sprzet'], 'Sprzętu nie prowadzi, tak jak kierownik budowy — kafelek pusty.');
    }

    public function test_raport_terminow_obejmuje_tylko_jego_ludzi(): void
    {
        $moj = $this->pracownikNa($this->mojaBudowa, 'Mojski');
        $obcy = $this->pracownikNa($this->cudzaBudowa, 'Obcy');

        $typ = \App\Models\BhpTyp::create(['name' => 'Szkolenie okresowe']);
        foreach ([$moj, $obcy] as $c) {
            \App\Models\Bhp::create([
                'contact_id' => $c->id, 'bhpTyp_id' => $typ->id,
                'start' => now()->subYear()->toDateString(),
                'end' => now()->addDays(10)->toDateString(),
            ]);
        }

        $props = $this->actingAs($this->opiekun)
            ->get('/reports/koniecUprawinien')->assertOk()->viewData('page')['props'];
        $nazwiska = collect($props['data'])->pluck('last_name');

        $this->assertContains('Mojski', $nazwiska);
        $this->assertNotContains('Obcy', $nazwiska);
    }

    public function test_zalozenie_konta_tworzy_kartoteke_i_wstawia_na_liste(): void
    {
        // O to prosił klient: jeden formularz. Lista wyboru przy budowie czyta
        // pracowników, więc samo konto by nie wystarczyło.
        $admin = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'admin@mkl.pl',
            'owner' => Role::ADMIN->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($admin)->post('/users', [
            'first_name' => 'Miłosz', 'last_name' => 'Pacak',
            'email' => 'milosz.pacak@mkl.pl',
            'owner' => (string) Role::KIEROWNIK_PROJEKTU->value,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $konto = User::where('email', 'milosz.pacak@mkl.pl')->firstOrFail();
        $kartoteka = Contact::where('user_id', $konto->id)->first();

        $this->assertNotNull($kartoteka, 'Konto bez kartoteki nie trafiłoby na listę wyboru.');
        $this->assertSame('Pacak', $kartoteka->last_name);
        $this->assertSame(Funkcja::kierownikProjektuId(), $kartoteka->funkcja_id);
        $this->assertNull($kartoteka->pesel, 'PESEL-u do tej roli nie zbieramy.');

        // Lista rozwijana przy zakładaniu budowy
        $props = $this->actingAs($admin)->get('/budowy/create')->assertOk()->viewData('page')['props'];
        $this->assertContains('Pacak Miłosz', collect($props['kierownicyProjektow'])->pluck('name'));
    }

    public function test_konto_dolacza_do_kartoteki_zalozonej_wczesniej(): void
    {
        // Ścieżka bez logowania zostaje, więc kartoteka mogła powstać wcześniej.
        // Zakładanie konta nie może dublować tej samej osoby.
        $admin = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'admin@mkl.pl',
            'owner' => Role::ADMIN->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $istniejaca = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Wojciech', 'last_name' => 'Szpura',
            'funkcja_id' => Funkcja::kierownikProjektuId(),
        ]);

        $this->actingAs($admin)->post('/users', [
            'first_name' => 'Wojciech', 'last_name' => 'Szpura',
            'email' => 'wojciech.szpura@mkl.pl',
            'owner' => (string) Role::KIEROWNIK_PROJEKTU->value,
        ])->assertRedirect();

        $konto = User::where('email', 'wojciech.szpura@mkl.pl')->firstOrFail();

        $this->assertSame(1, Contact::where('last_name', 'Szpura')->count(), 'Bez duplikatu kartoteki.');
        $this->assertSame($konto->id, $istniejaca->fresh()->user_id);
    }

    public function test_kartoteke_opiekuna_zapiszemy_bez_peselu_i_dat(): void
    {
        // Ścieżka bez logowania: wymagane tylko imię, nazwisko i stanowisko.
        $biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kadry@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($biuro)->post('/contacts', [
            'first_name' => 'Ewa', 'last_name' => 'Nowak',
            'funkcja_id' => Funkcja::kierownikProjektuId(),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('contacts', ['last_name' => 'Nowak', 'pesel' => null]);
    }

    public function test_zwyklemu_pracownikowi_dalej_wymagamy_kompletu(): void
    {
        $biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kadry@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $monter = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);

        $this->actingAs($biuro)->post('/contacts', [
            'first_name' => 'Jan', 'last_name' => 'Monterski', 'funkcja_id' => $monter->id,
        ])->assertSessionHasErrors(['pesel', 'birth_date', 'work_start', 'work_end']);
    }

    public function test_zdjecie_opieki_odbiera_dostep(): void
    {
        // Budowa przypięta jest polem, więc odpięcie musi zamknąć dostęp od razu.
        $this->mojaBudowa->update(['kierownik_projektu_id' => null]);

        $this->actingAs($this->opiekun)->get("/budowy/{$this->mojaBudowa->id}/edit")->assertForbidden();
    }
}
