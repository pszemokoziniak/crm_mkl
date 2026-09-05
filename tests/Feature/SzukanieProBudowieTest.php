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
use Tests\TestCase;

/**
 * Wyszukiwarka na liście pracowników: nazwisko, stanowisko oraz budowa —
 * po nazwie i po numerze. Budowy dotąd nie obejmowała, więc wpisanie
 * jej nazwy nie dawało żadnych wyników.
 */
class SzukanieProBudowieTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Organization $valmet;
    private Organization $andritz;

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

        $this->valmet = Organization::create([
            'account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '434_Valmet Ortofta', 'numerBud' => '434',
        ]);
        $this->andritz = Organization::create([
            'account_id' => 0, 'name' => 'Andritz', 'nazwaBud' => '453_Andritz Backhammar', 'numerBud' => '453',
        ]);
    }

    private function pracownik(string $nazwisko, ?Organization $budowa, string $start = null, string $end = null): Contact
    {
        $c = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
        ]);

        if ($budowa) {
            ContactWorkDate::create([
                'contact_id' => $c->id,
                'organization_id' => $budowa->id,
                'start' => $start ?? now()->subWeek()->toDateString(),
                'end' => $end ?? now()->addMonth()->toDateString(),
            ]);
        }

        return $c;
    }

    private function znalezieni(string $fraza, string $adres = '/contacts'): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get($adres.'?search='.urlencode($fraza));
        $odpowiedz->assertOk();

        return collect($odpowiedz->viewData('page')['props']['contacts']['data'])->pluck('last_name')->all();
    }

    public function test_szukanie_po_nazwie_budowy(): void
    {
        $this->pracownik('Valmetowski', $this->valmet);
        $this->pracownik('Andritzowski', $this->andritz);
        $this->pracownik('Bezbudowy', null);

        $this->assertSame(['Valmetowski'], $this->znalezieni('Valmet Ortofta'));
    }

    public function test_szukanie_po_numerze_budowy(): void
    {
        $this->pracownik('Valmetowski', $this->valmet);
        $this->pracownik('Andritzowski', $this->andritz);

        $this->assertSame(['Valmetowski'], $this->znalezieni('434'));
    }

    public function test_szukanie_po_nazwisku_dalej_dziala(): void
    {
        $this->pracownik('Kowalski', $this->valmet);
        $this->pracownik('Nowak', $this->andritz);

        $this->assertSame(['Kowalski'], $this->znalezieni('Kowal'));
    }

    public function test_szukanie_po_stanowisku_dalej_dziala(): void
    {
        $spawacz = Funkcja::create(['name' => 'Spawacz', 'kierownictwo' => false]);
        $c = $this->pracownik('Spawalski', $this->valmet);
        $c->update(['funkcja_id' => $spawacz->id]);

        $this->pracownik('Inny', $this->andritz);

        $this->assertSame(['Spawalski'], $this->znalezieni('Spawacz'));
    }

    public function test_znajduje_tez_bylych_pracownikow_budowy(): void
    {
        // Pobyt zakończony — w kolumnie "Koniec pobytu" i tak widnieje jako
        // "ostatnio", więc wyszukiwarka nie ma go ukrywać.
        $this->pracownik('Byly', $this->valmet, '2025-01-01', '2025-06-30');

        $this->assertSame(['Byly'], $this->znalezieni('Valmet'));
    }

    public function test_filtr_statusu_zaweza_do_obecnych(): void
    {
        $this->pracownik('Obecny', $this->valmet);
        $this->pracownik('Byly', $this->valmet, '2025-01-01', '2025-06-30');

        $odpowiedz = $this->actingAs($this->biuro)->get('/contacts?search=Valmet&status=na_budowie');
        $nazwiska = collect($odpowiedz->viewData('page')['props']['contacts']['data'])->pluck('last_name');

        $this->assertContains('Obecny', $nazwiska);
        $this->assertNotContains('Byly', $nazwiska);
    }

    public function test_pracownik_nie_dubluje_sie_przy_kilku_pobytach(): void
    {
        $c = $this->pracownik('Wielokrotny', $this->valmet);
        ContactWorkDate::create([
            'contact_id' => $c->id,
            'organization_id' => $this->valmet->id,
            'start' => '2025-01-01',
            'end' => '2025-03-31',
        ]);

        $this->assertSame(['Wielokrotny'], $this->znalezieni('Valmet'));
    }

    public function test_dziala_tez_w_zakladce_kierownikow(): void
    {
        $funkcja = Funkcja::create(['name' => 'Kierownik Budowy', 'kierownictwo' => true]);
        $c = $this->pracownik('Kierowniczak', $this->valmet);
        $c->update(['funkcja_id' => $funkcja->id]);

        $this->assertSame(['Kierowniczak'], $this->znalezieni('Valmet', '/kierownicy'));
    }
}
