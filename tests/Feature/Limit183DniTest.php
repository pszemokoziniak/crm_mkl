<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\KrajTyp;
use App\Models\Organization;
use App\Models\User;
use App\Services\LimitPobytuZagranica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Limit 183 dni pobytu w obcym państwie.
 *
 * Umowy podatkowe liczą dni w KAŻDYM okresie dwunastu miesięcy, liczonym
 * w przód od przyjazdu i wstecz od wyjazdu, a nie w roku kalendarzowym.
 * Na naszych danych sześć osób przekroczyło ten próg, jedna o 174 dni.
 */
class Limit183DniTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Contact $pracownik;
    private Organization $niemcy;
    private Organization $francja;
    private Organization $polska;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $niemcy = KrajTyp::create(['name' => 'Niemcy', 'wymaga_a1' => true]);
        $francja = KrajTyp::create(['name' => 'Francja', 'wymaga_a1' => true]);
        $polska = KrajTyp::create(['name' => 'Polska', 'wymaga_a1' => false]);

        $this->niemcy = $this->budowa('Siemens Berlin', $niemcy->id);
        $this->francja = $this->budowa('AET Lestrem', $francja->id);
        $this->polska = $this->budowa('Cementownia Kielce', $polska->id);

        $this->pracownik = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski',
        ]);
    }

    private function budowa(string $nazwa, int $krajId): Organization
    {
        return Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => $nazwa, 'country_id' => $krajId,
        ]);
    }

    private function pobyt(Organization $budowa, string $od, ?string $do): void
    {
        ContactWorkDate::create([
            'contact_id' => $this->pracownik->id,
            'organization_id' => $budowa->id,
            'start' => $od,
            'end' => $do,
        ]);
    }

    /** @return array<string, array<string, mixed>> wiersze wg kraju */
    private function wyliczenie(string $dzien = '2026-09-15'): array
    {
        return collect(app(LimitPobytuZagranica::class)->dlaPracownika($this->pracownik, $dzien))
            ->keyBy('kraj')->all();
    }

    public function test_liczy_dni_razem_z_przyjazdem_i_wyjazdem(): void
    {
        $this->pobyt($this->niemcy, '2026-09-01', '2026-09-10');

        $this->assertSame(10, $this->wyliczenie()['Niemcy']['dni_12m']);
        $this->assertSame(173, $this->wyliczenie()['Niemcy']['pozostalo']);
    }

    public function test_kilka_krotkich_pobytow_sumuje_sie(): void
    {
        // O to chodzi w przepisie: liczą się wszystkie wcześniejsze pobyty.
        $this->pobyt($this->niemcy, '2026-01-07', '2026-02-05');   // 30 dni
        $this->pobyt($this->niemcy, '2026-05-01', '2026-05-30');   // 30 dni

        $this->assertSame(60, $this->wyliczenie()['Niemcy']['dni_12m']);
    }

    public function test_kraj_macierzysty_nie_biegnie(): void
    {
        $this->pobyt($this->polska, '2026-01-01', '2026-08-31');

        $this->assertArrayNotHasKey('Polska', $this->wyliczenie());
    }

    public function test_kazdy_kraj_ma_wlasny_limit(): void
    {
        $this->pobyt($this->niemcy, '2026-03-01', '2026-04-30');
        $this->pobyt($this->francja, '2026-05-01', '2026-05-31');

        $wynik = $this->wyliczenie();

        $this->assertSame(61, $wynik['Niemcy']['dni_12m']);
        $this->assertSame(31, $wynik['Francja']['dni_12m']);
    }

    public function test_dni_sprzed_roku_wypadaja_z_okna(): void
    {
        // Sedno ruchomego okna: stary pobyt przestaje obciążać.
        $this->pobyt($this->niemcy, '2024-01-01', '2024-06-30');

        $this->assertSame(0, $this->wyliczenie()['Niemcy']['dni_12m']);
        $this->assertSame(182, $this->wyliczenie()['Niemcy']['najwieksze_okno'], 'Ale w historii wciąż widać, ile było.');
    }

    public function test_nakladajace_sie_pobyty_nie_licza_sie_podwojnie(): void
    {
        // Dwie budowy w tym samym kraju to wciąż jeden dzień za granicą.
        $this->pobyt($this->niemcy, '2026-09-01', '2026-09-10');
        $inna = $this->budowa('Oschatz', $this->niemcy->country_id);
        $this->pobyt($inna, '2026-09-05', '2026-09-15');

        $this->assertSame(15, $this->wyliczenie()['Niemcy']['dni_12m']);
    }

    public function test_przekroczenie_jest_oznaczone_i_podaje_okno(): void
    {
        $this->pobyt($this->niemcy, '2026-01-06', '2026-09-30');

        $wiersz = $this->wyliczenie()['Niemcy'];

        $this->assertSame('wyczerpany', $wiersz['status']);
        $this->assertSame(0, $wiersz['pozostalo']);
        $this->assertTrue($wiersz['kiedys_przekroczony']);
        $this->assertSame('2026-01-06', $wiersz['okno_od']);
    }

    public function test_okno_liczy_sie_takze_przez_przelom_roku(): void
    {
        // Rok podatkowy nie ma tu nic do rzeczy — liczy się dowolne 12 miesięcy.
        $this->pobyt($this->niemcy, '2025-11-01', '2025-12-31');  // 61
        $this->pobyt($this->niemcy, '2026-01-01', '2026-05-31');  // 151

        $wiersz = $this->wyliczenie();

        $this->assertSame(212, $wiersz['Niemcy']['najwieksze_okno']);
        $this->assertSame('wyczerpany', $wiersz['Niemcy']['status']);
    }

    public function test_dawne_przekroczenie_nie_blokuje_dzisiejszego_wyjazdu(): void
    {
        // Borowik ma we Francji 357 dni w oknie z 2025 roku, a dziś 53 dni
        // zapasu. Czerwień przy jego nazwisku wstrzymywałaby wyjazd bez powodu.
        $this->pobyt($this->francja, '2025-01-12', '2025-08-31');

        $wiersz = $this->wyliczenie()['Francja'];

        $this->assertSame(0, $wiersz['dni_12m']);
        $this->assertSame('ok', $wiersz['status'], 'Stan mówi o dziś.');
        $this->assertTrue($wiersz['kiedys_przekroczony'], 'Ale fakt zostaje widoczny.');
    }

    public function test_podaje_date_od_ktorej_limit_sie_poluzuje(): void
    {
        $this->pobyt($this->niemcy, '2026-01-06', '2026-09-30');

        $wolneOd = $this->wyliczenie()['Niemcy']['wolne_od'];

        $this->assertNotNull($wolneOd, 'Trzeba wiedzieć, kiedy znów można go wysłać.');
        $this->assertGreaterThan('2026-09-15', $wolneOd);
    }

    public function test_blisko_progu_zapala_sie_ostrzezenie(): void
    {
        $this->pobyt($this->niemcy, '2026-03-20', '2026-09-15');   // 180 dni

        $wiersz = $this->wyliczenie()['Niemcy'];

        $this->assertSame(3, $wiersz['pozostalo']);
        $this->assertSame('uwaga', $wiersz['status']);
    }

    public function test_karta_pracownika_podaje_zestawienie(): void
    {
        $biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => 2, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->pobyt($this->niemcy, '2026-03-01', '2026-04-30');

        $props = $this->actingAs($biuro)
            ->get('/contacts/'.$this->pracownik->id.'/edit')
            ->viewData('page')['props'];

        $this->assertSame('Niemcy', $props['limit_183'][0]['kraj']);
    }
}
