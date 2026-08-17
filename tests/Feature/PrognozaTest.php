<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Organization;
use App\Models\Prognoza;
use App\Models\PrognozaDates;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Wybór tygodni w prognozie pracowników — miesiąc z listy musi trafiać
 * we właściwe tygodnie, również na przełomie roku.
 */
class PrognozaTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Organization $budowa;

    protected function setUp(): void
    {
        parent::setUp();

        $accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $accountId,
            'email' => 'biuro@example.com',
            'owner' => 2,
            'active' => 1,
        ]);

        $this->budowa = Organization::create([
            'account_id' => $accountId,
            'name' => 'Grudziądz',
            'nazwaBud' => 'Grudziądz',
        ]);

        // Tygodnie poniedziałek-niedziela na przełomie 2026/2027.
        $poniedzialek = Carbon::create(2026, 11, 23);

        for ($i = 0; $i < 12; $i++) {
            $start = $poniedzialek->copy()->addWeeks($i);

            PrognozaDates::create([
                'start' => $start->format('Y-m-d'),
                'end' => $start->copy()->addDays(6)->format('Y-m-d'),
                'year' => $start->year,
            ]);
        }
    }

    public function test_wybrany_miesiac_daje_tygodnie_tego_miesiaca(): void
    {
        $this->actingAs($this->biuro)
            ->get('/prognoza/create?building='.$this->budowa->id.'&year=2026&month=12')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Prognoza/Create')
                ->has('dates', 4)
                ->where('dates.0.start', '2026-12-07')
                ->where('dates.3.start', '2026-12-28')
            );
    }

    public function test_styczen_nowego_roku_ma_swoje_tygodnie(): void
    {
        $this->actingAs($this->biuro)
            ->get('/prognoza/create?building='.$this->budowa->id.'&year=2027&month=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('dates', 4)
                ->where('dates.0.start', '2027-01-04')
                ->where('dates.3.start', '2027-01-25')
            );
    }

    public function test_kazdy_tydzien_nalezy_do_dokladnie_jednego_miesiaca(): void
    {
        $zebrane = [];

        foreach ([[2026, 11], [2026, 12], [2027, 1], [2027, 2]] as [$rok, $miesiac]) {
            $response = $this->actingAs($this->biuro)
                ->get('/prognoza/create?building='.$this->budowa->id.'&year='.$rok.'&month='.$miesiac);

            foreach ($response->viewData('page')['props']['dates'] as $date) {
                $zebrane[] = $date['start'];
            }
        }

        // Żaden tydzień nie może się powtórzyć ani zgubić między miesiącami.
        $this->assertSame($zebrane, array_values(array_unique($zebrane)));
        $this->assertCount(PrognozaDates::count(), $zebrane);
    }

    public function test_lista_lat_pomija_roczniki_bez_tygodni(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 17));

        $this->actingAs($this->biuro)
            ->get('/prognoza')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Prognoza/Index')
                ->where('years', [2026, 2027])
            );

        Carbon::setTestNow();
    }

    public function test_naglowek_konczy_sie_na_ostatnim_tygodniu_z_prognoza(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 17));

        $tydzien = PrognozaDates::where('start', '2026-12-07')->first();
        Prognoza::create([
            'organization_id' => $this->budowa->id,
            'prognoza_dates_id' => $tydzien->id,
            'workers_count' => 12,
        ]);

        $this->actingAs($this->biuro)
            ->get('/prognoza')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('startDateFormat', '2026-01-01')
                // Koniec zakresu = koniec ostatniego tygodnia z wpisem, nie "dziś + 6 lat".
                ->where('endDateFormat', $tydzien->end)
            );

        Carbon::setTestNow();
    }

    public function test_wykres_bez_wybranego_roku_obejmuje_kolejne_lata(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 17));

        $w2026 = PrognozaDates::where('start', '2026-12-07')->first();
        $w2027 = PrognozaDates::where('start', '2027-01-04')->first();

        foreach ([$w2026, $w2027] as $tydzien) {
            Prognoza::create([
                'organization_id' => $this->budowa->id,
                'prognoza_dates_id' => $tydzien->id,
                'workers_count' => 10,
            ]);
        }

        $this->actingAs($this->biuro)
            ->get('/prognoza')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Oba tygodnie na wykresie — nie tylko ten z bieżącego roku.
                ->has('chartData.labels', 2)
                ->where('endDateFormat', $w2027->end)
            );

        Carbon::setTestNow();
    }

    public function test_bez_zadnej_prognozy_naglowek_konczy_rok(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 17));

        $this->actingAs($this->biuro)
            ->get('/prognoza')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('endDateFormat', '2026-12-31'));

        Carbon::setTestNow();
    }
}
