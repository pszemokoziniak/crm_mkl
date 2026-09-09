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

    public function test_zdjecie_opieki_odbiera_dostep(): void
    {
        // Budowa przypięta jest polem, więc odpięcie musi zamknąć dostęp od razu.
        $this->mojaBudowa->update(['kierownik_projektu_id' => null]);

        $this->actingAs($this->opiekun)->get("/budowy/{$this->mojaBudowa->id}/edit")->assertForbidden();
    }
}
