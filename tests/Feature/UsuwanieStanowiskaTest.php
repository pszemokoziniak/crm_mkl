<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Funkcja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Usuwanie stanowiska ze słownika. Kartoteki pracowników trzymają się
 * stanowiska kluczem obcym, więc usunięcie używanego kończyło się błędem
 * bazy — teraz mówimy wprost, ile osób je ma.
 */
class UsuwanieStanowiskaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;

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
    }

    private function pracownik(Funkcja $funkcja, string $nazwisko): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
            'funkcja_id' => $funkcja->id,
        ]);
    }

    public function test_nieuzywane_stanowisko_da_sie_usunac(): void
    {
        $funkcja = Funkcja::create(['name' => 'Operator koparki', 'kierownictwo' => false]);

        $this->actingAs($this->biuro)
            ->delete('/funkcja/'.$funkcja->id)
            ->assertRedirect(route('funkcja'));

        $this->assertNull(Funkcja::find($funkcja->id));
        $this->assertSame('Stanowisko usunięte.', session('success'));
    }

    public function test_uzywane_stanowisko_zostaje_z_komunikatem(): void
    {
        $funkcja = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);
        $this->pracownik($funkcja, 'Kowalski');
        $this->pracownik($funkcja, 'Nowak');

        $this->actingAs($this->biuro)->delete('/funkcja/'.$funkcja->id);

        $this->assertNotNull(Funkcja::find($funkcja->id), 'Stanowisko zniknęło mimo przypisanych osób.');
        $this->assertStringContainsString('2 pracowników', session('error'));
        $this->assertStringContainsString('Monter konstrukcji stalowych', session('error'));
    }

    public function test_jedna_osoba_w_liczbie_pojedynczej(): void
    {
        $funkcja = Funkcja::create(['name' => 'Specjalista BHP', 'kierownictwo' => true]);
        $this->pracownik($funkcja, 'Kowalski');

        $this->actingAs($this->biuro)->delete('/funkcja/'.$funkcja->id);

        $this->assertStringContainsString('1 pracownika', session('error'));
    }

    public function test_zarchiwizowany_pracownik_tez_blokuje(): void
    {
        // Stanowisko wygląda na puste, bo kartoteka jest w archiwum —
        // a to właśnie ona wywoływała błąd bazy przy usuwaniu.
        $funkcja = Funkcja::create(['name' => 'Koordynator ds.realizacji projektów', 'kierownictwo' => false]);
        $this->pracownik($funkcja, 'Zarchiwizowany')->delete();

        $this->actingAs($this->biuro)->delete('/funkcja/'.$funkcja->id);

        $this->assertNotNull(Funkcja::find($funkcja->id));
        $this->assertStringContainsString('w archiwum', session('error'));
    }

    public function test_zmiana_nazwy_dziala_jak_dotad(): void
    {
        $funkcja = Funkcja::create(['name' => 'Monter', 'kierownictwo' => false]);
        $this->pracownik($funkcja, 'Kowalski');

        $this->actingAs($this->biuro)
            ->put('/funkcja/'.$funkcja->id, ['name' => 'Monter konstrukcji', 'kierownictwo' => true])
            ->assertRedirect();

        $this->assertSame('Monter konstrukcji', $funkcja->fresh()->name);
    }
}
