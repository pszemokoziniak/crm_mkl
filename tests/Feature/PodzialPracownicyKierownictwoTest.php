<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\ShiftStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Podział zakładek budowy: Pracownicy to obsada, Kierownictwo to kadra
 * kierownicza. Nikt nie figuruje w obu naraz, a obie listy pokazują to samo:
 * termin, stanowisko i to, czy ktoś dziś jest, czy ma nieobecność.
 */
class PodzialPracownicyKierownictwoTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Organization $budowa;
    private Funkcja $kierownik;
    private Funkcja $monter;

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

        $this->budowa = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '504_Valmet']);
        $this->kierownik = Funkcja::create(['name' => 'Kierownik Budowy', 'kierownictwo' => true]);
        $this->monter = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);
    }

    private function naBudowie(string $nazwisko, ?Funkcja $funkcja, ?string $koniec = null): Contact
    {
        $c = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
            'funkcja_id' => optional($funkcja)->id,
        ]);

        ContactWorkDate::create([
            'contact_id' => $c->id,
            'organization_id' => $this->budowa->id,
            'start' => now()->subMonth()->toDateString(),
            'end' => $koniec ?? now()->addMonth()->toDateString(),
        ]);

        return $c;
    }

    private function pracownicy(): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/pracownicy/'.$this->budowa->id);
        $odpowiedz->assertOk();

        return collect($odpowiedz->viewData('page')['props']['contactworkdates']['data'])
            ->pluck('contact.last_name')->all();
    }

    private function kierownictwo(): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/budowy/'.$this->budowa->id.'/kierownictwo');
        $odpowiedz->assertOk();

        return $odpowiedz->viewData('page')['props']['management'];
    }

    public function test_kierownik_nie_dubluje_sie_w_pracownikach(): void
    {
        $this->naBudowie('Kierowniczak', $this->kierownik);
        $this->naBudowie('Monterski', $this->monter);

        $this->assertSame(['Monterski'], $this->pracownicy());
        $this->assertSame(['Kierowniczak'], collect($this->kierownictwo())->pluck('last_name')->all());
    }

    public function test_pracownik_bez_stanowiska_zostaje_na_liscie(): void
    {
        // NOT IN samo z siebie pomija NULL — bez osobnego warunku taka osoba
        // zniknęłaby z obu zakładek.
        $this->naBudowie('Bezstanowiskowy', null);

        $this->assertSame(['Bezstanowiskowy'], $this->pracownicy());
    }

    public function test_kierownictwo_pokazuje_nieobecnosc(): void
    {
        $kierownikBudowy = $this->naBudowie('Kierowniczak', $this->kierownik);

        $urlop = ShiftStatus::create(['title' => 'Urlop wypoczynkowy', 'code' => 'UW']);
        Holiday::create([
            'contact_id' => $kierownikBudowy->id,
            'shift_status_id' => $urlop->id,
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addDay()->toDateString(),
        ]);

        $wiersz = $this->kierownictwo()[0];

        $this->assertTrue($wiersz['on_site']);
        $this->assertStringContainsString('Urlop wypoczynkowy', $wiersz['nieobecnosc']);
    }

    public function test_kierownictwo_odroznia_zakonczony_pobyt(): void
    {
        $this->naBudowie('Bylo', $this->kierownik, now()->subWeek()->toDateString());

        $wiersz = $this->kierownictwo()[0];

        $this->assertFalse($wiersz['on_site']);
        $this->assertNull($wiersz['nieobecnosc']);
    }

    public function test_kierownictwo_sortuje_polskim_alfabetem(): void
    {
        foreach (['Szymczak', 'Śledź', 'Sowa'] as $nazwisko) {
            $this->naBudowie($nazwisko, $this->kierownik);
        }

        $this->assertSame(
            ['Sowa', 'Szymczak', 'Śledź'],
            collect($this->kierownictwo())->pluck('last_name')->all()
        );
    }
}
