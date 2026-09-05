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
 * Karta sprzętu mówi, gdzie egzemplarz jest teraz i gdzie bywał —
 * z terminami. Dotąd pokazywała same liczniki i nazwę budowy bez dat.
 */
class KartaSprzetuTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Organization $budowaA;
    private Organization $budowaB;
    private Narzedzia $sprzet;

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

        $this->budowaA = Organization::create(['account_id' => 0, 'name' => 'Berkes', 'nazwaBud' => 'Berkes Lachendorf']);
        $this->budowaB = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => 'Valmet Ortofta']);

        $typ = NarzedziaTyp::create(['name' => 'Kontener 6m', 'kategoria' => 'Kontener']);

        $this->sprzet = Narzedzia::create([
            'name' => 'Kontener 6m',
            'narzedzia_typ_id' => $typ->id,
            'numer_seryjny' => 'SN-1',
            'waznosc_badan' => now()->addYear()->toDateString(),
            'ilosc_all' => 1,
            'ilosc_budowa' => 0,
        ]);
    }

    private function pobyt(Organization $o, ?string $start, ?string $end): ToolWorkDate
    {
        return ToolWorkDate::create([
            'narzedzia_id' => $this->sprzet->id,
            'organization_id' => $o->id,
            'narzedzia_nb' => 1,
            'start' => $start,
            'end' => $end,
        ]);
    }

    private function karta(): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/narzedzia/'.$this->sprzet->id.'/edit');
        $odpowiedz->assertOk();

        return $odpowiedz->viewData('page')['props']['narzedzia'];
    }

    public function test_sprzet_w_magazynie_nie_ma_budowy(): void
    {
        $karta = $this->karta();

        $this->assertNull($karta['gdzie_jest']);
        $this->assertSame([], $karta['pobyty']);
    }

    public function test_karta_pokazuje_budowe_i_terminy(): void
    {
        $this->pobyt($this->budowaA, '2026-09-01', '2026-12-22');

        $karta = $this->karta();

        $this->assertSame('Berkes Lachendorf', $karta['gdzie_jest']['nazwaBud']);
        $this->assertSame('2026-09-01', $karta['gdzie_jest']['od']);
        $this->assertSame('2026-12-22', $karta['gdzie_jest']['do']);
    }

    public function test_historia_od_najnowszego_ze_stanami(): void
    {
        $this->pobyt($this->budowaB, '2025-01-01', '2025-06-30');
        $this->pobyt($this->budowaA, '2026-09-01', '2026-12-22');
        $this->pobyt($this->budowaB, '2027-01-01', '2027-03-31');

        $pobyty = collect($this->karta()['pobyty']);

        $this->assertSame(['2027-01-01', '2026-09-01', '2025-01-01'], $pobyty->pluck('od')->all());
        $this->assertSame(['zaplanowany', 'trwa', 'zakonczony'], $pobyty->pluck('stan')->all());
    }

    public function test_zakonczony_pobyt_nie_jest_biezacym_miejscem(): void
    {
        $this->pobyt($this->budowaA, '2025-01-01', '2025-06-30');

        $karta = $this->karta();

        $this->assertNull($karta['gdzie_jest']);
        $this->assertSame('zakonczony', $karta['pobyty'][0]['stan']);
    }

    public function test_pobyt_bez_daty_konca_wciaz_trwa(): void
    {
        $this->pobyt($this->budowaA, '2026-01-01', null);

        $karta = $this->karta();

        $this->assertSame('Berkes Lachendorf', $karta['gdzie_jest']['nazwaBud']);
        $this->assertNull($karta['gdzie_jest']['do']);
        $this->assertSame('trwa', $karta['pobyty'][0]['stan']);
    }

    public function test_usunieta_budowa_nie_psuje_historii(): void
    {
        $this->pobyt($this->budowaA, '2026-09-01', '2026-12-22');
        $this->budowaA->delete();

        $this->actingAs($this->biuro)
            ->get('/narzedzia/'.$this->sprzet->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('narzedzia.pobyty.0.nazwaBud', 'budowa usunięta')
                ->etc()
            );
    }
}
