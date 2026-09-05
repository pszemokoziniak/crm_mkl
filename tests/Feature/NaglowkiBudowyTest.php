<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Każda podstrona budowy ma wiedzieć, o którą budowę chodzi — nagłówek
 * bierze nazwę z danych strony, więc te dane muszą tam być.
 */
class NaglowkiBudowyTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
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

        $this->budowa = Organization::create([
            'account_id' => 0,
            'name' => 'Vyncke',
            'nazwaBud' => '497_Vyncke Lugo',
        ]);
    }

    private function props(string $sciezka): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get($sciezka);
        $odpowiedz->assertOk();

        return $odpowiedz->viewData('page')['props'];
    }

    public function test_pracownicy_budowy_znaja_nazwe_budowy(): void
    {
        $props = $this->props('/pracownicy/'.$this->budowa->id);

        $this->assertSame('497_Vyncke Lugo', $props['organization']['nazwaBud']);
        $this->assertSame($this->budowa->id, $props['organization']['id']);
    }

    public function test_dane_klienta_znaja_nazwe_budowy_i_klienta(): void
    {
        $props = $this->props('/budowy/'.$this->budowa->id.'/klient');

        $this->assertSame('497_Vyncke Lugo', $props['organization']['nazwaBud']);
        // Klient budowy jest w jej karcie — zakładka ma go pokazywać,
        // nawet gdy nie dodano jeszcze żadnej osoby kontaktowej.
        $this->assertSame('Vyncke', $props['organization']['klient']);
        $this->assertCount(0, $props['klients']);
    }

    public function test_kcp_budowy_zna_nazwe_i_id(): void
    {
        $props = $this->props('/building/'.$this->budowa->id.'/time-sheet');

        $this->assertSame('497_Vyncke Lugo', $props['buildDetails']->nazwaBud);
        $this->assertSame($this->budowa->id, $props['buildDetails']->id);
    }

    public function test_kierownictwo_zna_nazwe_budowy(): void
    {
        $props = $this->props('/budowy/'.$this->budowa->id.'/kierownictwo');

        $this->assertSame('497_Vyncke Lugo', $props['organization']['nazwaBud']);
    }

    public function test_sprzet_budowy_zna_nazwe_budowy(): void
    {
        $props = $this->props('/budowy/'.$this->budowa->id.'/narzedzia');

        $this->assertSame('497_Vyncke Lugo', $props['organization']['nazwaBud']);
    }
}
