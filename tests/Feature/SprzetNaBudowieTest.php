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
use Tests\TestCase;

/**
 * Wydawanie sprzętu z karty budowy. Ekran pokazuje pojedyncze sztuki
 * z numerem seryjnym i badaniami — przy piętnastu identycznych kontenerach
 * inaczej nie wiadomo, który się bierze.
 */
class SprzetNaBudowieTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Organization $budowa;
    private NarzedziaTyp $kontener6;

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

        $this->budowa = Organization::create(['account_id' => 0, 'name' => 'LLT', 'nazwaBud' => '517_LLT Pontmain']);
        $this->kontener6 = NarzedziaTyp::create(['name' => 'Kontener 6m', 'kategoria' => 'Kontener']);
    }

    private function sztuka(string $numer, ?string $badania = null): Narzedzia
    {
        return Narzedzia::create([
            'name' => 'Kontener 6m',
            'narzedzia_typ_id' => $this->kontener6->id,
            'numer_seryjny' => $numer,
            'waznosc_badan' => $badania ?? now()->addYear()->toDateString(),
            'ilosc_all' => 1,
            'ilosc_budowa' => 0,
        ]);
    }

    private function ekran(): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/budowy/'.$this->budowa->id.'/narzedzia/create');
        $odpowiedz->assertOk();

        return $odpowiedz->viewData('page')['props'];
    }

    public function test_ekran_pokazuje_sztuki_z_numerami_seryjnymi(): void
    {
        $this->sztuka('SN-A');
        $this->sztuka('SN-B');

        $props = $this->ekran();
        $sztuki = collect($props['grupy'])->firstWhere('nazwa', 'Kontener')['modele'][0]['sztuki'];

        $this->assertSame(['SN-A', 'SN-B'], collect($sztuki)->pluck('numer_seryjny')->all());
        $this->assertArrayHasKey('badania_status', $sztuki[0]);
    }

    public function test_wydanie_zapisuje_termin(): void
    {
        $a = $this->sztuka('SN-A');

        $this->actingAs($this->biuro)
            ->post('/budowy/'.$this->budowa->id.'/narzedzia', [
                'narzedzia_ids' => [$a->id],
                'start' => '2026-09-05',
                'end' => '2026-11-30',
            ])
            ->assertRedirect();

        $wpis = ToolWorkDate::firstWhere('narzedzia_id', $a->id);

        $this->assertSame($this->budowa->id, $wpis->organization_id);
        $this->assertSame('2026-09-05', (string) $wpis->start);
        $this->assertSame('2026-11-30', (string) $wpis->end);
        $this->assertSame(1, $a->fresh()->ilosc_budowa);
    }

    public function test_wydany_sprzet_znika_z_listy_dostepnych(): void
    {
        $a = $this->sztuka('SN-A');
        $this->sztuka('SN-B');

        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/narzedzia', [
            'narzedzia_ids' => [$a->id],
            'start' => '2026-09-05',
        ]);

        $props = $this->ekran();
        $sztuki = collect($props['grupy'])->firstWhere('nazwa', 'Kontener')['modele'][0]['sztuki'];

        $this->assertSame(['SN-B'], collect($sztuki)->pluck('numer_seryjny')->all());
        $this->assertSame('SN-A', $props['naBudowie'][0]['numer_seryjny']);
        $this->assertSame('2026-09-05', $props['naBudowie'][0]['od']);
    }

    public function test_zakonczone_przypisanie_zwalnia_sztuke(): void
    {
        $a = $this->sztuka('SN-A');

        ToolWorkDate::create([
            'narzedzia_id' => $a->id,
            'organization_id' => $this->budowa->id,
            'narzedzia_nb' => 1,
            'start' => '2025-01-01',
            'end' => '2025-06-30',
        ]);
        // Licznik został po starym wydaniu — nie może decydować o dostępności.
        $a->update(['ilosc_budowa' => 1]);

        $sztuki = collect($this->ekran()['grupy'])->firstWhere('nazwa', 'Kontener')['modele'][0]['sztuki'];

        $this->assertSame(['SN-A'], collect($sztuki)->pluck('numer_seryjny')->all());
    }

    public function test_zajetej_sztuki_nie_wydamy_drugi_raz(): void
    {
        $a = $this->sztuka('SN-A');
        $inna = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => 'Inna']);

        ToolWorkDate::create([
            'narzedzia_id' => $a->id,
            'organization_id' => $inna->id,
            'narzedzia_nb' => 1,
            'start' => '2026-09-01',
            'end' => null,
        ]);

        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/narzedzia', [
            'narzedzia_ids' => [$a->id],
            'start' => '2026-09-05',
        ]);

        $this->assertSame(0, ToolWorkDate::where('organization_id', $this->budowa->id)->count());
    }

    public function test_data_do_nie_moze_byc_wczesniejsza(): void
    {
        $a = $this->sztuka('SN-A');

        $this->actingAs($this->biuro)
            ->post('/budowy/'.$this->budowa->id.'/narzedzia', [
                'narzedzia_ids' => [$a->id],
                'start' => '2026-09-10',
                'end' => '2026-09-01',
            ])
            ->assertSessionHasErrors('end');

        $this->assertSame(0, ToolWorkDate::count());
    }

    public function test_zdjecie_z_budowy_zwalnia_sztuke(): void
    {
        $a = $this->sztuka('SN-A');

        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/narzedzia', [
            'narzedzia_ids' => [$a->id],
            'start' => '2026-09-05',
        ]);

        $wpis = ToolWorkDate::firstWhere('narzedzia_id', $a->id);

        $this->actingAs($this->biuro)
            ->delete('/budowy/'.$this->budowa->id.'/narzedzia/'.$wpis->id.'/destroy')
            ->assertRedirect();

        $this->assertSame(0, ToolWorkDate::count());
        $this->assertSame(0, $a->fresh()->ilosc_budowa);
    }
}
