<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kto trafia na listę wyboru w zakładce Kierownictwo budowy.
 */
class KierownictwoBudowyTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private int $accountId;
    private Organization $budowa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro@example.com',
            'owner' => 2,
            'active' => 1,
        ]);
        $this->budowa = Organization::create([
            'account_id' => 0,
            'name' => 'Valmet',
            'nazwaBud' => '482_Vyncke Dornbirn',
        ]);
    }

    public function test_stanowiska_z_znacznikiem_trafiaja_na_liste(): void
    {
        $kierownikProjektu = $this->funkcja('Kierownik Projektu', true);
        $monter = $this->funkcja('Monter konstrukcji stalowych', false);

        $this->pracownik('Boryczka', $kierownikProjektu->id);
        $this->pracownik('Kowalski', $monter->id);

        $this->actingAs($this->biuro)
            ->get('/budowy/'.$this->budowa->id.'/kierownictwo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pracownicy/Kierownictwo')
                ->has('specialists', 1)
                ->where('specialists.0.last_name', 'Boryczka')
            );
    }

    public function test_zwolniony_nie_trafia_na_liste(): void
    {
        $funkcja = $this->funkcja('Specjalista BHP', true);
        $this->pracownik('Aktywny', $funkcja->id);
        $this->pracownik('Zwolniony', $funkcja->id)->update([
            'status_zatrudnienia' => Contact::STATUS_ZWOLNIONY,
        ]);

        $this->actingAs($this->biuro)
            ->get('/budowy/'.$this->budowa->id.'/kierownictwo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('specialists', 1)
                ->where('specialists.0.last_name', 'Aktywny')
            );
    }

    public function test_dodany_do_kierownictwa_pokazuje_sie_na_liscie_budowy(): void
    {
        $funkcja = $this->funkcja('Inżynier Spawalnik', true);
        $pracownik = $this->pracownik('Nowak', $funkcja->id);

        $this->actingAs($this->biuro)
            ->post('/budowy/'.$this->budowa->id.'/kierownictwo', [
                'contact_id' => $pracownik->id,
                'start' => '2026-09-01',
                'end' => '2026-12-31',
            ])
            ->assertRedirect();

        $this->actingAs($this->biuro)
            ->get('/budowy/'.$this->budowa->id.'/kierownictwo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('management', 1)
                ->where('management.0.last_name', 'Nowak')
            );
    }

    public function test_znacznik_da_sie_przestawic_w_ustawieniach(): void
    {
        $funkcja = $this->funkcja('Koordynator ds. Realizacji', false);
        $this->pracownik('Zieliński', $funkcja->id);

        // Zanim biuro zaznaczy stanowisko — nikogo na liście.
        $this->actingAs($this->biuro)
            ->get('/budowy/'.$this->budowa->id.'/kierownictwo')
            ->assertInertia(fn (Assert $page) => $page->has('specialists', 0));

        // Ustawienia → Stanowiska prowadzą kadry (biuro) same, bez admina.
        $this->actingAs($this->biuro)
            ->put('/funkcja/'.$funkcja->id, ['name' => $funkcja->name, 'kierownictwo' => true])
            ->assertRedirect();

        $this->actingAs($this->biuro)
            ->get('/budowy/'.$this->budowa->id.'/kierownictwo')
            ->assertInertia(fn (Assert $page) => $page->has('specialists', 1));
    }

    public function test_kierownictwo_mozna_dodac_mimo_kolizji_terminow(): void
    {
        $funkcja = $this->funkcja('Kierownik Budowy', true);
        $pracownik = $this->pracownik('Łagozin', $funkcja->id);
        $innaBudowa = Organization::create([
            'account_id' => 0,
            'name' => 'Inna',
            'nazwaBud' => 'Inna budowa',
        ]);

        ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $innaBudowa->id,
            'start' => '2026-09-01',
            'end' => '2026-09-30',
        ]);

        $this->actingAs($this->biuro)
            ->post('/budowy/'.$this->budowa->id.'/kierownictwo', [
                'contact_id' => $pracownik->id,
                'start' => '2026-09-07',
                'end' => '2026-09-16',
            ])
            ->assertRedirect();

        $this->assertSame(2, ContactWorkDate::where('contact_id', $pracownik->id)->count());
    }

    public function test_zwykly_pracownik_nie_przejdzie_ta_sciezka_przy_kolizji(): void
    {
        // Na liście wyboru go nie ma, ale samo żądanie dało się wcześniej wysłać
        // bez żadnego sprawdzenia — ta ścieżka nie pilnowała kolizji w ogóle.
        $funkcja = $this->funkcja('Monter konstrukcji stalowych', false);
        $pracownik = $this->pracownik('Borkowski', $funkcja->id);
        $innaBudowa = Organization::create([
            'account_id' => 0,
            'name' => 'Inna',
            'nazwaBud' => 'Inna budowa',
        ]);

        ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $innaBudowa->id,
            'start' => '2026-09-01',
            'end' => '2026-09-30',
        ]);

        $this->actingAs($this->biuro)
            ->post('/budowy/'.$this->budowa->id.'/kierownictwo', [
                'contact_id' => $pracownik->id,
                'start' => '2026-09-07',
                'end' => '2026-09-16',
            ])
            ->assertRedirect();

        $this->assertSame(1, ContactWorkDate::where('contact_id', $pracownik->id)->count());
    }

    public function test_strona_podaje_pobyty_do_ostrzezenia(): void
    {
        $funkcja = $this->funkcja('Inżynier Budowy', true);
        $pracownik = $this->pracownik('Nowak', $funkcja->id);

        ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $this->budowa->id,
            'start' => '2026-09-01',
            'end' => '2026-09-30',
        ]);

        $this->actingAs($this->biuro)
            ->get('/budowy/'.$this->budowa->id.'/kierownictwo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('pobyty.'.$pracownik->id, 1)
                ->where('pobyty.'.$pracownik->id.'.0.start', '2026-09-01')
            );
    }

    private function funkcja(string $nazwa, bool $kierownictwo): Funkcja
    {
        return Funkcja::create(['name' => $nazwa, 'kierownictwo' => $kierownictwo]);
    }

    private function pracownik(string $nazwisko, int $funkcjaId): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Wojciech',
            'last_name' => $nazwisko,
            'funkcja_id' => $funkcjaId,
        ]);
    }
}
