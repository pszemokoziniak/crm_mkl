<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\GrupaSprzetu;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grupy sprzętu: zakładanie, nazwa, usuwanie, przypisywanie modeli.
 *
 * Grupa jest osobnym rekordem właśnie po to, żeby mogła istnieć pusta —
 * dopóki była tekstem przy modelu, znikała w chwili, gdy ostatni model
 * ją opuścił, a literówka cicho tworzyła drugą grupę obok istniejącej.
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
        $typ = NarzedziaTyp::create([
            'name' => $nazwa,
            'grupa_id' => optional(GrupaSprzetu::zNazwy($grupa))->id,
        ]);

        for ($i = 0; $i < $sztuk; $i++) {
            Narzedzia::create(['name' => $nazwa.' '.$i, 'narzedzia_typ_id' => $typ->id]);
        }

        return $typ;
    }

    private function props(): array
    {
        return $this->actingAs($this->admin)->get('/grupy-sprzetu')->viewData('page')['props'];
    }

    public function test_ekran_pokazuje_grupy_z_licznikami(): void
    {
        $this->model('Manitou MRT 2150', 'Manitou', 2);
        $this->model('Manitou MT 1840', 'Manitou', 1);
        $this->model('Kontener 6m', 'Kontener', 5);
        $this->model('Magni RTH 6.30', null, 1);

        $p = $this->props();

        $manitou = collect($p['grupy'])->firstWhere('nazwa', 'Manitou');
        $this->assertSame(2, $manitou['modeli']);
        $this->assertSame(3, $manitou['sztuk'], 'Licznik sztuk sumuje egzemplarze wszystkich modeli grupy.');
        $this->assertCount(1, $p['bezGrupy']);
        $this->assertSame('Magni RTH 6.30', $p['bezGrupy'][0]['name']);
    }

    public function test_mozna_zalozyc_pusta_grupe(): void
    {
        // Sedno zmiany: magazyn zakłada grupę z góry i dopiero potem
        // wrzuca do niej sprzęt. Wcześniej było to niewykonalne.
        $this->actingAs($this->admin)
            ->post('/grupy-sprzetu', ['nazwa' => 'Żuraw'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('grupy_sprzetu', ['nazwa' => 'Żuraw']);

        $grupa = collect($this->props()['grupy'])->firstWhere('nazwa', 'Żuraw');
        $this->assertSame(0, $grupa['modeli'], 'Pusta grupa jest widoczna na liście…');
        $this->assertSame(0, $grupa['sztuk']);
    }

    public function test_pusta_grupa_przyjmuje_model(): void
    {
        $this->actingAs($this->admin)->post('/grupy-sprzetu', ['nazwa' => 'Żuraw']);
        $grupa = GrupaSprzetu::where('nazwa', 'Żuraw')->firstOrFail();
        $typ = $this->model('Liebherr 81 K.1', null, 2);

        $this->actingAs($this->admin)
            ->post('/grupy-sprzetu/przypisz', ['modele' => [$typ->id], 'grupa_id' => $grupa->id])
            ->assertRedirect();

        $this->assertSame($grupa->id, $typ->fresh()->grupa_id);
        $this->assertSame(2, collect($this->props()['grupy'])->firstWhere('nazwa', 'Żuraw')['sztuk']);
    }

    public function test_grupa_o_zajetej_nazwie_nie_powstaje_drugi_raz(): void
    {
        $this->model('Manitou MT 1840', 'Manitou');

        // Wielkość liter nie tworzy nowej grupy — inaczej "manitou" stanęłoby
        // obok "Manitou" i nikt by tego na ekranie nie odróżnił.
        $this->actingAs($this->admin)
            ->post('/grupy-sprzetu', ['nazwa' => '  manitou '])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, GrupaSprzetu::count());
    }

    public function test_zmiana_nazwy_przestawia_wszystkie_modele_grupy(): void
    {
        $a = $this->model('Manitou MRT 2150', 'Manitou');
        $b = $this->model('Manitou MT 1840', 'Manitou');
        $obcy = $this->model('Kontener 6m', 'Kontener');
        $grupa = GrupaSprzetu::where('nazwa', 'Manitou')->firstOrFail();

        $this->actingAs($this->admin)
            ->put("/grupy-sprzetu/{$grupa->id}", ['nazwa' => 'Manitou (podnośniki)'])
            ->assertRedirect();

        // Modele nie ruszają się z miejsca — zmienia się sama nazwa grupy.
        $this->assertSame('Manitou (podnośniki)', $a->fresh()->nazwaGrupy());
        $this->assertSame('Manitou (podnośniki)', $b->fresh()->nazwaGrupy());
        $this->assertSame('Kontener', $obcy->fresh()->nazwaGrupy(), 'Inne grupy zostają nietknięte.');
    }

    public function test_zmiana_na_istniejaca_nazwe_laczy_grupy(): void
    {
        $zLiterowka = $this->model('Manitou MT 1840', 'Manitu');
        $poprawny = $this->model('Manitou MRT 2150', 'Manitou');
        $zla = GrupaSprzetu::where('nazwa', 'Manitu')->firstOrFail();

        $this->actingAs($this->admin)
            ->put("/grupy-sprzetu/{$zla->id}", ['nazwa' => 'Manitou'])
            ->assertRedirect();

        // Po to jest ta ścieżka: scalenie grupy powstałej z literówki.
        $this->assertSame('Manitou', $zLiterowka->fresh()->nazwaGrupy());
        $this->assertSame('Manitou', $poprawny->fresh()->nazwaGrupy());
        $this->assertCount(1, $this->props()['grupy'], 'Pusta grupa po literówce znika, nie zostaje sierotą.');
    }

    public function test_usuniecie_grupy_nie_kasuje_sprzetu(): void
    {
        $typ = $this->model('Kontener 6m', 'Kontener', 4);
        $grupa = GrupaSprzetu::where('nazwa', 'Kontener')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete("/grupy-sprzetu/{$grupa->id}")
            ->assertRedirect();

        $this->assertNull($typ->fresh()->grupa_id, 'Model traci grupę…');
        $this->assertDatabaseHas('narzedzia_typs', ['id' => $typ->id]);
        $this->assertSame(4, Narzedzia::where('narzedzia_typ_id', $typ->id)->count(), '…ale sprzęt zostaje.');
    }

    public function test_model_mozna_przeniesc_do_innej_grupy(): void
    {
        $typ = $this->model('Manitou MT 1840', 'Kontener');
        $this->model('Manitou MRT 2150', 'Manitou');
        $manitou = GrupaSprzetu::where('nazwa', 'Manitou')->firstOrFail();

        $this->actingAs($this->admin)
            ->post('/grupy-sprzetu/przypisz', ['modele' => [$typ->id], 'grupa_id' => $manitou->id])
            ->assertRedirect();

        $this->assertSame('Manitou', $typ->fresh()->nazwaGrupy());
    }

    public function test_model_mozna_wyjac_z_grupy(): void
    {
        $typ = $this->model('Magni RTH 6.30', 'Manitou');

        $this->actingAs($this->admin)
            ->post('/grupy-sprzetu/przypisz', ['modele' => [$typ->id], 'grupa_id' => null])
            ->assertRedirect();

        $this->assertNull($typ->fresh()->grupa_id);
    }

    public function test_nieistniejaca_grupa_daje_404_a_nie_bledu_bazy(): void
    {
        $this->actingAs($this->admin)
            ->put('/grupy-sprzetu/9999', ['nazwa' => 'Cokolwiek'])
            ->assertNotFound();
    }

    public function test_magazyn_grupuje_sprzet_po_nowej_grupie(): void
    {
        // Ekran magazynu czyta grupę przez relację; gdyby została na starej
        // kolumnie, cały sprzęt rozsypałby się na osobne pozycje.
        $this->model('Kontener 3m', 'Kontener', 1);
        $this->model('Kontener 6m', 'Kontener', 2);

        $grupy = $this->actingAs($this->admin)->get('/narzedzia')->viewData('page')['props']['grupy'];

        $kontener = collect($grupy)->firstWhere('nazwa', 'Kontener');
        $this->assertNotNull($kontener, 'Magazyn pokazuje grupę, a nie dwa osobne modele.');
        $this->assertTrue($kontener['ma_modele']);
        $this->assertSame(3, $kontener['sztuk']);
    }

    public function test_biuro_prowadzi_grupy_sprzetu(): void
    {
        // Magazynem zajmuje się biuro, nie administrator — to biuro zgłosiło
        // potrzebę zakładania grup i to ono przypisuje do nich sprzęt.
        $biuro = $this->uzytkownik(Role::BIURO, 'biuro@mkl.pl');
        $typ = $this->model('Kontener 6m', null);

        $this->actingAs($biuro)->get('/grupy-sprzetu')->assertOk();
        $this->actingAs($biuro)->post('/grupy-sprzetu', ['nazwa' => 'Kontener'])->assertRedirect();

        $grupa = GrupaSprzetu::where('nazwa', 'Kontener')->firstOrFail();
        $this->actingAs($biuro)
            ->post('/grupy-sprzetu/przypisz', ['modele' => [$typ->id], 'grupa_id' => $grupa->id])
            ->assertRedirect();

        $this->assertSame('Kontener', $typ->fresh()->nazwaGrupy());
    }

    public function test_kierownik_budowy_nie_rusza_slownika(): void
    {
        $kierownik = $this->uzytkownik(Role::KIEROWNIK, 'kierownik@mkl.pl');
        $typ = $this->model('Kontener 6m', 'Kontener');
        $grupa = GrupaSprzetu::where('nazwa', 'Kontener')->firstOrFail();

        $this->actingAs($kierownik)->get('/grupy-sprzetu')->assertForbidden();
        $this->actingAs($kierownik)->post('/grupy-sprzetu', ['nazwa' => 'X'])->assertForbidden();
        $this->actingAs($kierownik)->put("/grupy-sprzetu/{$grupa->id}", ['nazwa' => 'X'])->assertForbidden();
        $this->actingAs($kierownik)->delete("/grupy-sprzetu/{$grupa->id}")->assertForbidden();

        $this->assertSame('Kontener', $typ->fresh()->nazwaGrupy());
        $this->assertSame(1, GrupaSprzetu::count());
    }

    private function uzytkownik(Role $rola, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->admin->account_id, 'email' => $email,
            'owner' => $rola->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }
}
