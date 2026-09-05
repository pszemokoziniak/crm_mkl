<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\Organization;
use App\Models\ToolWorkDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Magazyn sprzętu: jeden wiersz na rodzaj, w środku pojedyncze sztuki
 * z numerem seryjnym i badaniami. Z tej listy wydaje się sprzęt na budowę
 * i przyjmuje z powrotem.
 */
class MagazynSprzetuTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private NarzedziaTyp $kontener6;
    private NarzedziaTyp $kontener3;
    private Organization $budowa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->biuro = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->kontener6 = NarzedziaTyp::create(['name' => 'Kontener 6m', 'kategoria' => 'Kontener']);
        $this->kontener3 = NarzedziaTyp::create(['name' => 'Kontener 3m', 'kategoria' => 'Kontener']);

        $this->budowa = Organization::create([
            'account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '504_Valmet Ortofta',
        ]);
    }

    private function sztuka(NarzedziaTyp $typ, string $numer, ?string $badania = null): Narzedzia
    {
        return Narzedzia::create([
            'name' => $typ->name,
            'narzedzia_typ_id' => $typ->id,
            'numer_seryjny' => $numer,
            'waznosc_badan' => $badania ?? now()->addYear()->toDateString(),
            'ilosc_all' => 1,
            'ilosc_budowa' => 0,
        ]);
    }

    private function grupy(): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/narzedzia');
        $odpowiedz->assertOk();

        return $odpowiedz->viewData('page')['props']['grupy'];
    }

    /** @return array<string, mixed> */
    private function model(string $nazwa): array
    {
        foreach ($this->grupy() as $grupa) {
            foreach ($grupa['modele'] as $model) {
                if ($model['nazwa'] === $nazwa) {
                    return $model;
                }
            }
        }

        $this->fail('Nie ma modelu '.$nazwa.' na liście.');
    }

    /** @return array<int, array<string, mixed>> */
    private function sztukiModelu(string $nazwa): array
    {
        return $this->model($nazwa)['sztuki'];
    }

    public function test_kategoria_zbiera_modele_i_sumuje_sztuki(): void
    {
        $this->sztuka($this->kontener6, 'A1');
        $this->sztuka($this->kontener6, 'A2');
        $this->sztuka($this->kontener3, 'B1');

        $grupy = collect($this->grupy());

        // Jeden wiersz "Kontener", w środku dwa modele.
        $this->assertCount(1, $grupy);

        $kontener = $grupy->first();
        $this->assertSame('Kontener', $kontener['nazwa']);
        $this->assertTrue($kontener['ma_modele']);
        $this->assertSame(3, $kontener['sztuk']);
        $this->assertSame(3, $kontener['dostepne']);

        $modele = collect($kontener['modele']);
        $this->assertSame(2, $modele->firstWhere('nazwa', 'Kontener 6m')['sztuk']);
        $this->assertSame(1, $modele->firstWhere('nazwa', 'Kontener 3m')['sztuk']);
    }

    public function test_sprzet_bez_kategorii_stoi_osobno(): void
    {
        $jlg = NarzedziaTyp::create(['name' => 'JLG X20J Plus']);
        $this->sztuka($jlg, 'J1');
        $this->sztuka($this->kontener6, 'A1');

        $grupy = collect($this->grupy());

        $bezKategorii = $grupy->firstWhere('nazwa', 'JLG X20J Plus');
        $this->assertFalse($bezKategorii['ma_modele']);
        $this->assertSame(1, $bezKategorii['sztuk']);
    }

    public function test_grupa_pokazuje_pojedyncze_sztuki(): void
    {
        $this->sztuka($this->kontener6, 'SN-111', '2027-05-31');

        $sztuki = $this->sztukiModelu('Kontener 6m');

        $this->assertSame('SN-111', $sztuki[0]['numer_seryjny']);
        $this->assertSame('2027-05-31', $sztuki[0]['waznosc_badan']);
        $this->assertNull($sztuki[0]['budowa']);
    }

    public function test_smieciowe_daty_badan_traktujemy_jak_brak(): void
    {
        $this->sztuka($this->kontener6, 'SN-222', '9999-12-31');

        $sztuka = $this->sztukiModelu('Kontener 6m')[0];

        $this->assertNull($sztuka['waznosc_badan']);
        $this->assertSame('brak', $sztuka['badania_status']);
    }

    public function test_grupa_rozdziela_badania_po_terminie_i_koncsace_sie(): void
    {
        $this->sztuka($this->kontener6, 'SN-1', now()->subMonth()->toDateString());
        $this->sztuka($this->kontener6, 'SN-2', now()->addDays(10)->toDateString());
        $this->sztuka($this->kontener6, 'SN-3', now()->addYear()->toDateString());
        // Bez daty nie liczy się do żadnej z grup — inaczej sygnał utonąłby
        // w sprzęcie, któremu daty nigdy nie wpisano.
        $this->sztuka($this->kontener6, 'SN-4', '9999-12-31');

        $model = $this->model('Kontener 6m');

        $this->assertSame(1, $model['badania_po_terminie']);
        $this->assertSame(1, $model['badania_wkrotce']);
        $this->assertSame(2, $model['badania_uwaga']);

        $kategoria = collect($this->grupy())->firstWhere('nazwa', 'Kontener');
        $this->assertSame(1, $kategoria['badania_po_terminie']);
        $this->assertSame(1, $kategoria['badania_wkrotce']);
    }

    public function test_wydanie_sprzetu_na_budowe(): void
    {
        $a = $this->sztuka($this->kontener6, 'A1');
        $b = $this->sztuka($this->kontener6, 'A2');

        $this->actingAs($this->biuro)
            ->post('/narzedzia/przypisz', [
                'narzedzia_ids' => [$a->id, $b->id],
                'organization_id' => $this->budowa->id,
                'start' => '2026-09-05',
                'end' => '2026-12-31',
            ])
            ->assertRedirect('/narzedzia');

        $this->assertSame(2, ToolWorkDate::where('organization_id', $this->budowa->id)->count());
        $this->assertSame(1, $a->fresh()->ilosc_budowa);

        $model = $this->model('Kontener 6m');
        $this->assertSame(2, $model['na_budowie']);
        $this->assertSame(0, $model['dostepne']);
    }

    public function test_sprzet_juz_na_budowie_nie_idzie_drugi_raz(): void
    {
        $a = $this->sztuka($this->kontener6, 'A1');

        $this->actingAs($this->biuro)->post('/narzedzia/przypisz', [
            'narzedzia_ids' => [$a->id],
            'organization_id' => $this->budowa->id,
            'start' => '2026-09-05',
        ]);

        $inna = Organization::create(['account_id' => 0, 'name' => 'Andritz', 'nazwaBud' => 'Inna budowa']);

        $this->actingAs($this->biuro)->post('/narzedzia/przypisz', [
            'narzedzia_ids' => [$a->id],
            'organization_id' => $inna->id,
            'start' => '2026-09-06',
        ]);

        $this->assertSame(0, ToolWorkDate::where('organization_id', $inna->id)->count());
        $this->assertSame(1, $a->fresh()->ilosc_budowa);
    }

    public function test_zdjecie_z_budowy_wraca_do_magazynu(): void
    {
        $a = $this->sztuka($this->kontener6, 'A1');

        $this->actingAs($this->biuro)->post('/narzedzia/przypisz', [
            'narzedzia_ids' => [$a->id],
            'organization_id' => $this->budowa->id,
            'start' => '2026-09-05',
        ]);

        $przypisanie = ToolWorkDate::where('narzedzia_id', $a->id)->firstOrFail();

        $this->actingAs($this->biuro)
            ->delete('/narzedzia/przypisanie/'.$przypisanie->id)
            ->assertRedirect('/narzedzia');

        $this->assertSame(0, ToolWorkDate::count());
        $this->assertSame(0, $a->fresh()->ilosc_budowa);

        $this->assertSame(1, $this->model('Kontener 6m')['dostepne']);
    }

    public function test_zakonczone_przypisanie_zwalnia_sprzet(): void
    {
        $a = $this->sztuka($this->kontener6, 'A1');

        ToolWorkDate::create([
            'narzedzia_id' => $a->id,
            'organization_id' => $this->budowa->id,
            'narzedzia_nb' => 1,
            'start' => '2025-01-01',
            'end' => '2025-06-30',
        ]);

        $model = $this->model('Kontener 6m');

        $this->assertSame(1, $model['dostepne']);
        $this->assertSame(0, $model['na_budowie']);
    }

    public function test_data_do_nie_moze_byc_wczesniejsza(): void
    {
        $a = $this->sztuka($this->kontener6, 'A1');

        $this->actingAs($this->biuro)
            ->post('/narzedzia/przypisz', [
                'narzedzia_ids' => [$a->id],
                'organization_id' => $this->budowa->id,
                'start' => '2026-09-10',
                'end' => '2026-09-01',
            ])
            ->assertSessionHasErrors('end');

        $this->assertSame(0, ToolWorkDate::count());
    }

    public function test_lista_podaje_budowy_do_wyboru(): void
    {
        $this->sztuka($this->kontener6, 'A1');

        $this->actingAs($this->biuro)
            ->get('/narzedzia')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Narzedzia/Index')
                ->where('budowy.0.nazwaBud', '504_Valmet Ortofta')
                ->etc()
            );
    }

    public function test_kierownik_nie_wydaje_sprzetu(): void
    {
        $kierownik = User::factory()->create([
            'account_id' => $this->biuro->account_id,
            'email' => 'kierownik@mkl.pl',
            'owner' => 3,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $a = $this->sztuka($this->kontener6, 'A1');

        $this->actingAs($kierownik)
            ->post('/narzedzia/przypisz', [
                'narzedzia_ids' => [$a->id],
                'organization_id' => $this->budowa->id,
                'start' => '2026-09-05',
            ])
            ->assertStatus(403);
    }
}
