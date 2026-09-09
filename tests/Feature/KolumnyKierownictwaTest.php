<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kolumny "Kierownik budowy" i "Inżynier" na liście budów.
 *
 * Liczyły się zapytaniem `where funkcja_id = 1` i `= 6`, więc z 43 osób na
 * stanowiskach kierowniczych widać było 19 — reszta znikała bez śladu.
 * Przypisanie stanowiska do kolumny jest teraz w słowniku, żeby kolejne
 * stanowisko biuro ustawiło samo, bez wdrożenia.
 */
class KolumnyKierownictwaTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Organization $budowa;
    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->budowa = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Lausitzer Zeitz',
        ]);
    }

    private function stanowisko(string $nazwa, ?string $rola): Funkcja
    {
        return Funkcja::create([
            'name' => $nazwa,
            'kierownictwo' => $rola !== null,
            'rola_budowy' => $rola,
        ]);
    }

    private function naBudowie(string $nazwisko, Funkcja $stanowisko): Contact
    {
        $osoba = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan',
            'last_name' => $nazwisko, 'funkcja_id' => $stanowisko->id,
        ]);

        ContactWorkDate::create([
            'contact_id' => $osoba->id,
            'organization_id' => $this->budowa->id,
            'start' => now()->subMonth()->toDateString(),
            'end' => null,
        ]);

        return $osoba;
    }

    private function wiersz(): array
    {
        $props = $this->actingAs($this->biuro)->get('/budowy')->viewData('page')['props'];

        return collect($props['organizations']['data'] ?? $props['organizations'])
            ->firstWhere('nazwaBud', 'Lausitzer Zeitz');
    }

    public function test_kolumna_kierownika_zbiera_wszystkie_przypisane_stanowiska(): void
    {
        // Sedno zgłoszenia: "Kierownik - budowy GW Polska" to też kierownik,
        // a przy zaszytym funkcja_id = 1 nie pokazywał się nigdzie.
        $this->naBudowie('Adamczyk', $this->stanowisko('Kierownik Budowy', Funkcja::ROLA_KIEROWNIK));
        $this->naBudowie('Bącik', $this->stanowisko('Kierownik - budowy GW Polska', Funkcja::ROLA_KIEROWNIK));
        $this->naBudowie('Cebula', $this->stanowisko('Monter konstrukcji stalowych', null));

        $kierownicy = $this->wiersz()['kierownicy'];

        $this->assertStringContainsString('Adamczyk', $kierownicy);
        $this->assertStringContainsString('Bącik', $kierownicy);
        $this->assertStringNotContainsString('Cebula', $kierownicy, 'Monter nie jest kierownikiem.');
    }

    public function test_kolumna_inzyniera_zbiera_wszystkie_przypisane_stanowiska(): void
    {
        $this->naBudowie('Dudek', $this->stanowisko('Inżynier Budowy', Funkcja::ROLA_INZYNIER));
        $this->naBudowie('Ewiak', $this->stanowisko('Koordynator ds. Realizacji', Funkcja::ROLA_INZYNIER));
        $this->naBudowie('Fiedler', $this->stanowisko('Specjalista BHP', Funkcja::ROLA_INZYNIER));
        $this->naBudowie('Gałka', $this->stanowisko('Inżynier Spawalnik', Funkcja::ROLA_INZYNIER));

        $inzynierowie = $this->wiersz()['inzynierowie'];

        foreach (['Dudek', 'Ewiak', 'Fiedler', 'Gałka'] as $nazwisko) {
            $this->assertStringContainsString($nazwisko, $inzynierowie);
        }
    }

    public function test_kierownik_projektu_nie_wchodzi_do_kolumny_kierownika(): void
    {
        // Ma własną kolumnę i własne pole na budowie — nie miesza się z resztą.
        $this->naBudowie('Hałas', $this->stanowisko('Kierownik Projektu', Funkcja::ROLA_KIEROWNIK_PROJEKTU));

        $wiersz = $this->wiersz();

        $this->assertNull($wiersz['kierownicy']);
        $this->assertNull($wiersz['inzynierowie']);
    }

    public function test_nowe_stanowisko_dodaje_biuro_bez_wdrozenia(): void
    {
        // O to chodziło w tej zmianie: przypisanie do kolumny jest w słowniku.
        $stanowisko = $this->stanowisko('Kierownik robót', null);
        $this->naBudowie('Iksiński', $stanowisko);

        $this->assertNull($this->wiersz()['kierownicy'], 'Bez przypisania kolumna go nie bierze.');

        $this->actingAs($this->biuro)
            ->put("/funkcja/{$stanowisko->id}", [
                'name' => 'Kierownik robót',
                'kierownictwo' => true,
                'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
            ])
            ->assertRedirect();

        $this->assertStringContainsString('Iksiński', $this->wiersz()['kierownicy']);
    }

    public function test_slownik_pokazuje_i_zapisuje_przypisanie(): void
    {
        $stanowisko = $this->stanowisko('Inżynier Budowy', Funkcja::ROLA_INZYNIER);

        $props = $this->actingAs($this->biuro)->get("/funkcja/{$stanowisko->id}/edit")->viewData('page')['props'];

        $this->assertSame(Funkcja::ROLA_INZYNIER, $props['funkcja']['rola_budowy']);
        $this->assertSame(Funkcja::ROLE_BUDOWY, $props['roleBudowy'], 'Ekran musi znać podpisy kolumn.');
    }

    public function test_bledna_kolumna_nie_przechodzi(): void
    {
        $stanowisko = $this->stanowisko('Inżynier Budowy', Funkcja::ROLA_INZYNIER);

        $this->actingAs($this->biuro)
            ->put("/funkcja/{$stanowisko->id}", ['name' => 'Inżynier Budowy', 'rola_budowy' => 'cokolwiek'])
            ->assertSessionHasErrors('rola_budowy');

        $this->assertSame(Funkcja::ROLA_INZYNIER, $stanowisko->fresh()->rola_budowy);
    }
}
