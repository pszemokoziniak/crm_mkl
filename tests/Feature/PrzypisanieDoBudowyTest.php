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
 * Lista budów w formularzu "Przypisz do budowy" na karcie pracownika.
 */
class PrzypisanieDoBudowyTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->biuro = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'biuro@example.com',
            'owner' => 2,
            'active' => 1,
        ]);
    }

    public function test_lista_zawiera_budowy_mimo_innego_account_id(): void
    {
        // Budowy zapisują się z account_id = 0, użytkownicy mają 1 —
        // filtr po koncie zwracał pustą listę i select był pusty.
        $budowa = Organization::create([
            'account_id' => 0,
            'name' => 'Valmet',
            'nazwaBud' => '434_Valmet Reko Holandia',
        ]);

        $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik()->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('organizations', 1)
                ->where('organizations.0.id', $budowa->id)
                ->where('organizations.0.nazwaBud', '434_Valmet Reko Holandia')
            );
    }

    public function test_lista_pomija_zarchiwizowane_i_sortuje_po_nazwie_budowy(): void
    {
        Organization::create(['account_id' => 0, 'name' => 'Zeta', 'nazwaBud' => 'B_Druga']);
        Organization::create(['account_id' => 0, 'name' => 'Alfa', 'nazwaBud' => 'A_Pierwsza']);
        Organization::create(['account_id' => 0, 'name' => 'Stara', 'nazwaBud' => 'C_Zamknieta'])->delete();

        $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik()->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('organizations', 2)
                ->where('organizations.0.nazwaBud', 'A_Pierwsza')
                ->where('organizations.1.nazwaBud', 'B_Druga')
            );
    }

    public function test_formularz_dodawania_pracownika_tez_ma_budowy(): void
    {
        Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '434_Valmet']);

        $this->actingAs($this->biuro)
            ->get('/contacts/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('organizations', 1));
    }

    public function test_przypisanie_do_budowy_zapisuje_pobyt(): void
    {
        $budowa = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '434_Valmet']);
        $pracownik = $this->pracownik();

        $this->actingAs($this->biuro)
            ->post('/contacts/'.$pracownik->id.'/przypisz-budowe', [
                'organization_id' => $budowa->id,
                'start' => '2026-10-01',
                'end' => '2026-10-31',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contact_work_dates', [
            'contact_id' => $pracownik->id,
            'organization_id' => $budowa->id,
            'start' => '2026-10-01',
        ]);
    }

    public function test_monter_nie_przejdzie_gdy_terminy_sie_nakladaja(): void
    {
        $biuro = $this->biuro;
        $monter = $this->pracownikNaStanowisku('Monter konstrukcji stalowych', false);
        [$budowaA, $budowaB] = $this->dwieBudowy();

        $this->pobyt($monter, $budowaA, '2026-09-01', '2026-09-30');

        $this->actingAs($biuro)
            ->post('/contacts/'.$monter->id.'/przypisz-budowe', [
                'organization_id' => $budowaB->id,
                'start' => '2026-09-07',
                'end' => '2026-09-16',
            ])
            ->assertRedirect();

        $this->assertSame(1, ContactWorkDate::where('contact_id', $monter->id)->count());
    }

    public function test_kierownictwo_moze_byc_na_dwoch_budowach_naraz(): void
    {
        $biuro = $this->biuro;
        $inzynier = $this->pracownikNaStanowisku('Inżynier Budowy', true);
        [$budowaA, $budowaB] = $this->dwieBudowy();

        $this->pobyt($inzynier, $budowaA, '2026-09-01', '2026-09-30');

        $this->actingAs($biuro)
            ->post('/contacts/'.$inzynier->id.'/przypisz-budowe', [
                'organization_id' => $budowaB->id,
                'start' => '2026-09-07',
                'end' => '2026-09-16',
            ])
            ->assertRedirect();

        $this->assertSame(2, ContactWorkDate::where('contact_id', $inzynier->id)->count());
    }

    public function test_karta_podaje_pobyty_i_znacznik_do_ostrzezenia(): void
    {
        $biuro = $this->biuro;
        $inzynier = $this->pracownikNaStanowisku('Inżynier Budowy', true);
        [$budowaA] = $this->dwieBudowy();
        $this->pobyt($inzynier, $budowaA, '2026-09-01', '2026-09-30');

        $this->actingAs($biuro)
            ->get('/contacts/'.$inzynier->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('czyKierownictwo', true)
                ->has('wszystkiePobyty', 1)
                ->where('wszystkiePobyty.0.start', '2026-09-01')
            );
    }

    private function pracownikNaStanowisku(string $nazwa, bool $kierownictwo): Contact
    {
        $funkcja = Funkcja::create(['name' => $nazwa, 'kierownictwo' => $kierownictwo]);

        return Contact::create([
            'account_id' => $this->biuro->account_id,
            'first_name' => 'Mateusz',
            'last_name' => 'Ambroziak',
            'funkcja_id' => $funkcja->id,
        ]);
    }

    /** @return Organization[] */
    private function dwieBudowy(): array
    {
        return [
            Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => 'Valmet Louvain']),
            Organization::create(['account_id' => 0, 'name' => 'Vyncke', 'nazwaBud' => 'Vyncke Świdno']),
        ];
    }

    private function pobyt(Contact $pracownik, Organization $budowa, string $start, string $end): ContactWorkDate
    {
        return ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $budowa->id,
            'start' => $start,
            'end' => $end,
        ]);
    }

    private function pracownik(): Contact
    {
        return Contact::create([
            'account_id' => $this->biuro->account_id,
            'first_name' => 'Piotr',
            'last_name' => 'Baran',
        ]);
    }
}
