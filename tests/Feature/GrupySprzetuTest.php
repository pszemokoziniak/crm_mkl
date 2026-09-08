<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grupy sprzętu. To nie osobna tabela, tylko kolumna `kategoria` przy modelu,
 * więc dotąd nie dało się grupy przemianować ani usunąć — trzeba było
 * poprawiać każdy model z osobna i trafić w tę samą pisownię.
 */
class GrupySprzetuTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $accountId = Account::create(['name' => 'MKL'])->id;

        $this->admin = User::factory()->create([
            'account_id' => $accountId, 'email' => 'admin@mkl.pl',
            'owner' => Role::ADMIN->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function model(string $nazwa, ?string $grupa, int $sztuk = 0): NarzedziaTyp
    {
        $typ = NarzedziaTyp::create(['name' => $nazwa, 'kategoria' => $grupa]);

        for ($i = 0; $i < $sztuk; $i++) {
            Narzedzia::create(['name' => $nazwa.' '.$i, 'narzedzia_typ_id' => $typ->id]);
        }

        return $typ;
    }

    public function test_ekran_pokazuje_grupy_z_licznikami(): void
    {
        $this->model('Manitou MRT 2150', 'Manitou', 2);
        $this->model('Manitou MT 1840', 'Manitou', 1);
        $this->model('Kontener 6m', 'Kontener', 5);
        $this->model('Magni RTH 6.30', null, 1);

        $p = $this->actingAs($this->admin)->get('/grupy-sprzetu')->viewData('page')['props'];

        $manitou = collect($p['grupy'])->firstWhere('nazwa', 'Manitou');
        $this->assertSame(2, $manitou['modeli']);
        $this->assertSame(3, $manitou['sztuk'], 'Licznik sztuk sumuje egzemplarze wszystkich modeli grupy.');
        $this->assertCount(1, $p['bezGrupy']);
        $this->assertSame('Magni RTH 6.30', $p['bezGrupy'][0]['name']);
    }

    public function test_zmiana_nazwy_przestawia_wszystkie_modele_grupy(): void
    {
        $a = $this->model('Manitou MRT 2150', 'Manitou');
        $b = $this->model('Manitou MT 1840', 'Manitou');
        $obcy = $this->model('Kontener 6m', 'Kontener');

        $this->actingAs($this->admin)
            ->put('/grupy-sprzetu', ['stara' => 'Manitou', 'nowa' => 'Manitou (podnośniki)'])
            ->assertRedirect();

        $this->assertSame('Manitou (podnośniki)', $a->fresh()->kategoria);
        $this->assertSame('Manitou (podnośniki)', $b->fresh()->kategoria);
        $this->assertSame('Kontener', $obcy->fresh()->kategoria, 'Inne grupy zostają nietknięte.');
    }

    public function test_zmiana_na_istniejaca_nazwe_laczy_grupy(): void
    {
        $zLiterowka = $this->model('Manitou MT 1840', 'Manitu');
        $poprawny = $this->model('Manitou MRT 2150', 'Manitou');

        $this->actingAs($this->admin)
            ->put('/grupy-sprzetu', ['stara' => 'Manitu', 'nowa' => 'Manitou'])
            ->assertRedirect();

        // Po to jest ta ścieżka: scalenie grupy powstałej z literówki.
        $this->assertSame('Manitou', $zLiterowka->fresh()->kategoria);
        $this->assertSame('Manitou', $poprawny->fresh()->kategoria);
        $this->assertCount(1, $this->actingAs($this->admin)->get('/grupy-sprzetu')->viewData('page')['props']['grupy']);
    }

    public function test_usuniecie_grupy_nie_kasuje_sprzetu(): void
    {
        $typ = $this->model('Kontener 6m', 'Kontener', 4);

        $this->actingAs($this->admin)
            ->delete('/grupy-sprzetu', ['nazwa' => 'Kontener'])
            ->assertRedirect();

        $this->assertNull($typ->fresh()->kategoria, 'Model traci grupę…');
        $this->assertDatabaseHas('narzedzia_typs', ['id' => $typ->id]);
        $this->assertSame(4, Narzedzia::where('narzedzia_typ_id', $typ->id)->count(), '…ale sprzęt zostaje.');
    }

    public function test_model_mozna_przeniesc_do_innej_grupy(): void
    {
        $typ = $this->model('Manitou MT 1840', 'Kontener');
        $this->model('Manitou MRT 2150', 'Manitou');

        $this->actingAs($this->admin)
            ->post('/grupy-sprzetu/przypisz', ['modele' => [$typ->id], 'grupa' => 'Manitou'])
            ->assertRedirect();

        $this->assertSame('Manitou', $typ->fresh()->kategoria);
    }

    public function test_model_mozna_wyjac_z_grupy(): void
    {
        $typ = $this->model('Magni RTH 6.30', 'Manitou');

        $this->actingAs($this->admin)
            ->post('/grupy-sprzetu/przypisz', ['modele' => [$typ->id], 'grupa' => null])
            ->assertRedirect();

        $this->assertNull($typ->fresh()->kategoria);
    }

    public function test_nieistniejaca_grupa_daje_komunikat_a_nie_blad(): void
    {
        $this->actingAs($this->admin)
            ->put('/grupy-sprzetu', ['stara' => 'Nie ma takiej', 'nowa' => 'Cokolwiek'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_tylko_administrator(): void
    {
        $biuro = User::factory()->create([
            'account_id' => $this->admin->account_id, 'email' => 'biuro@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
        $typ = $this->model('Kontener 6m', 'Kontener');

        $this->actingAs($biuro)->get('/grupy-sprzetu')->assertForbidden();
        $this->actingAs($biuro)->put('/grupy-sprzetu', ['stara' => 'Kontener', 'nowa' => 'X'])->assertForbidden();
        $this->actingAs($biuro)->delete('/grupy-sprzetu', ['nazwa' => 'Kontener'])->assertForbidden();

        $this->assertSame('Kontener', $typ->fresh()->kategoria);
    }
}
