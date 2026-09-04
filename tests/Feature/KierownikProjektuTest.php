<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Funkcja;
use App\Models\KrajTyp;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kierownik projektu — opiekun kontraktu wybierany z listy pracowników
 * ze stanowiskiem "Kierownik Projektu" (słownik /funkcja). To ktoś inny
 * niż kierownik budowy.
 */
class KierownikProjektuTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private int $krajId;
    private int $funkcjaId;
    private int $innaFunkcjaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->biuro = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'biuro@example.com',
            'owner' => 2,
            'active' => 1,
        ]);

        $this->krajId = KrajTyp::create(['name' => 'Polska'])->id;
        $this->funkcjaId = Funkcja::create(['name' => Funkcja::NAZWA_KIEROWNIK_PROJEKTU])->id;
        $this->innaFunkcjaId = Funkcja::create(['name' => 'Spawacz'])->id;
    }

    public function test_nowa_budowa_zapisuje_kierownika_projektu(): void
    {
        $osoba = $this->pracownik('Nowak', 'Anna', $this->funkcjaId);

        $this->actingAs($this->biuro)
            ->post('/budowy', [
                'name' => 'Valmet',
                'nazwaBud' => '500_Nowa budowa',
                'country_id' => $this->krajId,
                'kierownik_projektu_id' => $osoba->id,
            ])
            ->assertRedirect();

        $this->assertSame(
            $osoba->id,
            Organization::firstWhere('nazwaBud', '500_Nowa budowa')->kierownik_projektu_id
        );
    }

    public function test_edycja_budowy_zmienia_kierownika_projektu(): void
    {
        $stary = $this->pracownik('Kowalski', 'Jan', $this->funkcjaId);
        $nowy = $this->pracownik('Zieliński', 'Piotr', $this->funkcjaId);
        $budowa = $this->budowa($stary->id);

        $this->actingAs($this->biuro)
            ->put('/budowy/'.$budowa->id, [
                'name' => $budowa->name,
                'nazwaBud' => $budowa->nazwaBud,
                'kierownik_projektu_id' => $nowy->id,
            ])
            ->assertRedirect();

        $this->assertSame($nowy->id, $budowa->fresh()->kierownik_projektu_id);
    }

    public function test_pole_moze_zostac_puste(): void
    {
        $budowa = $this->budowa($this->pracownik('Kowalski', 'Jan', $this->funkcjaId)->id);

        $this->actingAs($this->biuro)
            ->put('/budowy/'.$budowa->id, [
                'name' => $budowa->name,
                'nazwaBud' => $budowa->nazwaBud,
                'kierownik_projektu_id' => null,
            ])
            ->assertRedirect();

        $this->assertNull($budowa->fresh()->kierownik_projektu_id);
    }

    public function test_nieistniejacy_pracownik_jest_odrzucany(): void
    {
        $osoba = $this->pracownik('Kowalski', 'Jan', $this->funkcjaId);
        $budowa = $this->budowa($osoba->id);

        $this->actingAs($this->biuro)
            ->put('/budowy/'.$budowa->id, [
                'name' => $budowa->name,
                'nazwaBud' => $budowa->nazwaBud,
                'kierownik_projektu_id' => 999999,
            ])
            ->assertSessionHasErrors('kierownik_projektu_id');

        $this->assertSame($osoba->id, $budowa->fresh()->kierownik_projektu_id);
    }

    public function test_do_wyboru_sa_tylko_pracownicy_z_tym_stanowiskiem(): void
    {
        $nowak = $this->pracownik('Nowak', 'Anna', $this->funkcjaId);
        $kowalski = $this->pracownik('Kowalski', 'Jan', $this->funkcjaId);
        $this->pracownik('Spawalski', 'Adam', $this->innaFunkcjaId);
        $this->pracownik('Zwolniony', 'Marek', $this->funkcjaId, Contact::STATUS_ZWOLNIONY);

        $this->actingAs($this->biuro)
            ->get('/budowy/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organizations/Create')
                ->where('kierownicyProjektow', [
                    ['id' => $kowalski->id, 'name' => 'Kowalski Jan'],
                    ['id' => $nowak->id, 'name' => 'Nowak Anna'],
                ])
            );
    }

    public function test_juz_przypisany_zwolniony_zostaje_na_liscie(): void
    {
        $zwolniony = $this->pracownik('Zwolniony', 'Marek', $this->funkcjaId, Contact::STATUS_ZWOLNIONY);
        $budowa = $this->budowa($zwolniony->id);

        $this->actingAs($this->biuro)
            ->get('/budowy/'.$budowa->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organization.kierownik_projektu_id', $zwolniony->id)
                ->where('kierownicyProjektow', [
                    ['id' => $zwolniony->id, 'name' => 'Zwolniony Marek'],
                ])
            );
    }

    public function test_lista_budow_pokazuje_nazwisko_kierownika_projektu(): void
    {
        $this->budowa($this->pracownik('Nowak', 'Anna', $this->funkcjaId)->id);

        $this->actingAs($this->biuro)
            ->get('/budowy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organizations.data.0.kierownik_projektu', 'Nowak Anna')
            );
    }

    private function pracownik(string $nazwisko, string $imie, int $funkcjaId, ?string $status = null): Contact
    {
        return Contact::create([
            'account_id' => 0,
            'first_name' => $imie,
            'last_name' => $nazwisko,
            'funkcja_id' => $funkcjaId,
            'status_zatrudnienia' => $status ?? Contact::STATUS_AKTYWNY,
        ]);
    }

    private function budowa(?int $kierownikProjektuId): Organization
    {
        return Organization::create([
            'account_id' => 0,
            'name' => 'Klient',
            'nazwaBud' => 'Budowa '.uniqid(),
            'country_id' => $this->krajId,
            'kierownik_projektu_id' => $kierownikProjektuId,
        ]);
    }
}
