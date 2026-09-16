<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wyszukiwarka osób: kilka słów = wszystkie muszą pasować, "-słowo" wyklucza.
 * Zgłoszenie: "wszyscy kierownicy bez tych, którzy pracują na budowach GW".
 */
class WyszukiwanieOsobTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Funkcja $kierownik;
    private Funkcja $kierownikGw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->kierownik = Funkcja::create(['id' => 1, 'name' => 'Kierownik Budowy', 'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK]);
        $this->kierownikGw = Funkcja::create(['id' => 16, 'name' => 'Kierownik - budowy GW Polska', 'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK]);
    }

    private function osoba(string $nazwisko, string $imie, Funkcja $funkcja, ?Organization $budowa = null, bool $trwa = true): Contact
    {
        $c = Contact::create(['account_id' => $this->accountId, 'first_name' => $imie, 'last_name' => $nazwisko, 'funkcja_id' => $funkcja->id]);
        if ($budowa) {
            ContactWorkDate::create([
                'contact_id' => $c->id, 'organization_id' => $budowa->id,
                'start' => now()->subYear()->toDateString(),
                'end' => $trwa ? now()->addMonth()->toDateString() : now()->subMonths(6)->toDateString(),
            ]);
        }

        return $c;
    }

    /** @return string[] nazwiska w kolejności z listy */
    private function szukaj(string $fraza): array
    {
        return Contact::kierownictwo(true)->orderByName()->filter(['search' => $fraza])->pluck('last_name')->all();
    }

    public function test_rozbija_slowa_na_wymagane_i_wykluczone(): void
    {
        $this->assertSame([['Jan', 'Kowalski'], ['GW']], Contact::rozbijWyszukiwanie('  Jan +Kowalski -GW '));
        $this->assertSame([[], []], Contact::rozbijWyszukiwanie('- +'));
    }

    public function test_minus_wyklucza_stanowisko_i_dzisiejsza_budowe(): void
    {
        $gw = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Orlen Płock', 'numerBud' => 'GW-7']);
        $inna = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Valmet', 'numerBud' => 'B-1']);

        $this->osoba('Adamski', 'Jan', $this->kierownik, $inna);
        $this->osoba('Bielski', 'Jan', $this->kierownikGw, $inna);      // stanowisko GW
        $this->osoba('Celuch', 'Jan', $this->kierownik, $gw);           // dziś na budowie GW-7
        $this->osoba('Dudek', 'Jan', $this->kierownik, $gw, false);     // był na GW-7 pół roku temu
        $this->osoba('Ewert', 'Jan', $this->kierownik);                 // bez budowy

        $this->assertSame(['Adamski', 'Bielski', 'Celuch', 'Dudek', 'Ewert'], $this->szukaj(''));
        $this->assertSame(['Adamski', 'Dudek', 'Ewert'], $this->szukaj('-GW'));
        // Szukanie "na plus" bierze też stare pobyty — jak dotąd.
        $this->assertSame(['Bielski', 'Celuch', 'Dudek'], $this->szukaj('GW'));
    }

    public function test_kilka_slow_musi_pasowac_naraz_i_da_sie_je_laczyc_z_minusem(): void
    {
        $valmet = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Valmet Ortofta']);
        $this->osoba('Kowalski', 'Jan', $this->kierownik, $valmet);
        $this->osoba('Kowalski', 'Piotr', $this->kierownikGw, $valmet);
        $this->osoba('Nowak', 'Jan', $this->kierownik);

        // Imię i nazwisko to osobne kolumny — dotąd "Jan Kowalski" nie znajdował nikogo.
        $this->assertSame(['Kowalski'], $this->szukaj('Jan Kowalski'));
        $this->assertSame(['Kowalski', 'Kowalski'], $this->szukaj('Valmet'));
        $this->assertSame(['Kowalski'], $this->szukaj('Valmet -GW'));
        $this->assertSame('Jan', Contact::kierownictwo(true)->filter(['search' => 'Valmet -GW'])->value('first_name'));
    }
}
