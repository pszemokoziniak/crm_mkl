<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\KrajTyp;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kierownik projektu — wpisywany ręcznie opiekun kontraktu.
 * To ktoś inny niż kierownik budowy i nie ma go w bazie pracowników.
 */
class KierownikProjektuTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private int $krajId;

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
    }

    public function test_nowa_budowa_zapisuje_kierownika_projektu(): void
    {
        $this->actingAs($this->biuro)
            ->post('/budowy', [
                'name' => 'Valmet',
                'nazwaBud' => '500_Nowa budowa',
                'country_id' => $this->krajId,
                'kierownik_projektu' => 'Anna Nowak',
            ])
            ->assertRedirect();

        $this->assertSame('Anna Nowak', Organization::firstWhere('nazwaBud', '500_Nowa budowa')->kierownik_projektu);
    }

    public function test_edycja_budowy_zmienia_kierownika_projektu(): void
    {
        $budowa = $this->budowa('Jan Kowalski');

        $this->actingAs($this->biuro)
            ->put('/budowy/'.$budowa->id, [
                'name' => $budowa->name,
                'nazwaBud' => $budowa->nazwaBud,
                'kierownik_projektu' => 'Piotr Zieliński',
            ])
            ->assertRedirect();

        $this->assertSame('Piotr Zieliński', $budowa->fresh()->kierownik_projektu);
    }

    public function test_pole_moze_zostac_puste(): void
    {
        $budowa = $this->budowa(null);

        $this->actingAs($this->biuro)
            ->put('/budowy/'.$budowa->id, [
                'name' => $budowa->name,
                'nazwaBud' => $budowa->nazwaBud,
                'kierownik_projektu' => null,
            ])
            ->assertRedirect();

        $this->assertNull($budowa->fresh()->kierownik_projektu);
    }

    public function test_zbyt_dlugie_nazwisko_jest_odrzucane(): void
    {
        $budowa = $this->budowa('Jan Kowalski');

        $this->actingAs($this->biuro)
            ->put('/budowy/'.$budowa->id, [
                'name' => $budowa->name,
                'nazwaBud' => $budowa->nazwaBud,
                'kierownik_projektu' => str_repeat('a', 150),
            ])
            ->assertSessionHasErrors('kierownik_projektu');

        $this->assertSame('Jan Kowalski', $budowa->fresh()->kierownik_projektu);
    }

    public function test_formularz_podpowiada_wczesniej_wpisane_nazwiska(): void
    {
        $this->budowa('Anna Nowak');
        $this->budowa('Jan Kowalski');
        $this->budowa('Anna Nowak');   // duplikat — na liście ma być raz
        $this->budowa(null);

        $this->actingAs($this->biuro)
            ->get('/budowy/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organizations/Create')
                ->where('kierownicyProjektow', ['Anna Nowak', 'Jan Kowalski'])
            );
    }

    public function test_lista_budow_pokazuje_kierownika_projektu(): void
    {
        $this->budowa('Anna Nowak');

        $this->actingAs($this->biuro)
            ->get('/budowy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organizations.data.0.kierownik_projektu', 'Anna Nowak')
            );
    }

    private function budowa(?string $kierownikProjektu): Organization
    {
        return Organization::create([
            'account_id' => 0,
            'name' => 'Klient',
            'nazwaBud' => 'Budowa '.uniqid(),
            'country_id' => $this->krajId,
            'kierownik_projektu' => $kierownikProjektu,
        ]);
    }
}
