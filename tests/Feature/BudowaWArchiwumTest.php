<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Archiwizacja budowy nie kasuje pobytów, więc historia pracownika ma
 * dalej pokazywać jej nazwę — z dopiskiem, że budowa jest w archiwum.
 */
class BudowaWArchiwumTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Contact $pracownik;
    private Organization $budowa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->budowa = Organization::create([
            'account_id' => 0, 'name' => 'Andritz', 'nazwaBud' => '455_Andritz Aanekoski',
        ]);

        $this->pracownik = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Tomasz',
            'last_name' => 'Wiercioch',
        ]);
    }

    private function pobyt(string $start, string $end): ContactWorkDate
    {
        return ContactWorkDate::create([
            'contact_id' => $this->pracownik->id,
            'organization_id' => $this->budowa->id,
            'start' => $start,
            'end' => $end,
        ]);
    }

    private function karta(): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/contacts/'.$this->pracownik->id.'/edit');
        $odpowiedz->assertOk();

        return $odpowiedz->viewData('page')['props'];
    }

    public function test_historia_pokazuje_nazwe_zarchiwizowanej_budowy(): void
    {
        $this->pobyt('2025-04-25', '2025-06-21');
        $this->budowa->delete();

        $pobyt = collect($this->karta()['wszystkiePobyty'])->first();

        $this->assertSame('455_Andritz Aanekoski', $pobyt['nazwaBud']);
        $this->assertTrue($pobyt['budowa_w_archiwum']);
    }

    public function test_czynna_budowa_nie_jest_oznaczona(): void
    {
        $this->pobyt('2026-08-01', '2026-10-31');

        $pobyt = collect($this->karta()['wszystkiePobyty'])->first();

        $this->assertSame('455_Andritz Aanekoski', $pobyt['nazwaBud']);
        $this->assertFalse($pobyt['budowa_w_archiwum']);
    }

    public function test_trwajace_przypisanie_tez_zna_nazwe(): void
    {
        $this->pobyt('2026-08-01', now()->addMonth()->toDateString());
        $this->budowa->delete();

        $przypisanie = collect($this->karta()['przypisania'])->first();

        $this->assertSame('455_Andritz Aanekoski', $przypisanie['nazwaBud']);
        $this->assertTrue($przypisanie['budowa_w_archiwum']);
    }

    public function test_komunikat_o_kolizji_nazywa_budowe_z_archiwum(): void
    {
        $this->pobyt('2026-09-01', '2026-12-31');
        $this->budowa->delete();

        $inna = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => 'Inna budowa']);

        $this->actingAs($this->biuro)->post('/contacts/'.$this->pracownik->id.'/przypisz-budowe', [
            'organization_id' => $inna->id,
            'start' => '2026-10-01',
            'end' => '2026-11-30',
        ]);

        $this->assertStringContainsString('455_Andritz Aanekoski (w archiwum)', session('error'));
    }
}
