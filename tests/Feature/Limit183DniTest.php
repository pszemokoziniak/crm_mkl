<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
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

        $niemcy = KrajTyp::create([
            'name' => 'Niemcy', 'wymaga_a1' => true, 'sposob_183' => KrajTyp::SPOSOB_12M,
        ]);
        $francja = KrajTyp::create([
            'name' => 'Francja', 'wymaga_a1' => true, 'sposob_183' => KrajTyp::SPOSOB_ROK,
        ]);
        $polska = KrajTyp::create(['name' => 'Polska', 'wymaga_a1' => false]);

        $this->niemcy = $this->budowa('Siemens Berlin', $niemcy->id);
        $this->francja = $this->budowa('AET Lestrem', $francja->id);
        $this->polska = $this->budowa('Cementownia Kielce', $polska->id);

        // Lista kandydatów łączy pracowników ze słownikiem stanowisk,
        // więc bez stanowiska ten pracownik w ogóle by się tam nie pojawił.
        $monter = Funkcja::create(['name' => 'Monter', 'kierownictwo' => false]);

        $this->pracownik = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski',
            'funkcja_id' => $monter->id, 'status_zatrudnienia' => Contact::STATUS_AKTYWNY,
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

    private function biuro(): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro'.uniqid().'@mkl.pl',
            'owner' => 2, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_pulpit_ostrzega_gdy_zostalo_malo_dni(): void
    {
        // Pobyt trwa nadal, więc licznik biegnie — to jest moment, w którym
        // biuro ma się dowiedzieć, a nie pół roku później.
        $this->pobyt($this->niemcy, '2026-03-20', null);

        $props = $this->actingAs($this->biuro())->get('/')->viewData('page')['props'];
        $wiersz = collect($props['expiring_items'])->firstWhere('category', 'Limit 183 dni');

        $this->assertNotNull($wiersz, 'Limit to termin jak każdy inny.');
        $this->assertSame('Niemcy', $wiersz['type']);
        $this->assertSame('Kowalski', $wiersz['contact']['last_name']);
    }

    public function test_pulpit_milczy_gdy_zapasu_jest_duzo(): void
    {
        $this->pobyt($this->niemcy, '2026-09-01', null);

        $props = $this->actingAs($this->biuro())->get('/')->viewData('page')['props'];

        $this->assertNull(collect($props['expiring_items'])->firstWhere('category', 'Limit 183 dni'));
    }

    public function test_raport_terminow_wymienia_limit(): void
    {
        $this->pobyt($this->niemcy, '2026-03-20', null);

        $props = $this->actingAs($this->biuro())
            ->get('/reports/koniecUprawinien')->viewData('page')['props'];

        $wiersz = collect($props['data'])->firstWhere('category', 'Limit 183 dni');

        $this->assertNotNull($wiersz);
        $this->assertStringContainsString('Niemcy', $wiersz['name']);
    }

    public function test_przypisanie_do_budowy_ostrzega_o_przekroczeniu(): void
    {
        $this->pobyt($this->niemcy, '2026-01-06', '2026-06-30');

        $this->actingAs($this->biuro())
            ->post('/contacts/'.$this->pracownik->id.'/przypisz-budowe', [
                'organization_id' => $this->niemcy->id,
                'start' => '2026-10-01',
                'end' => '2026-11-30',
            ])
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('contact_work_dates', 2, );
    }

    public function test_przypisanie_w_granicach_limitu_nie_straszy(): void
    {
        $this->actingAs($this->biuro())
            ->post('/contacts/'.$this->pracownik->id.'/przypisz-budowe', [
                'organization_id' => $this->niemcy->id,
                'start' => '2026-10-01',
                'end' => '2026-10-31',
            ])
            ->assertSessionHas('success');
    }

    public function test_lista_kandydatow_pokazuje_pozostale_dni_w_kraju_budowy(): void
    {
        $this->pobyt($this->niemcy, '2026-03-20', '2026-09-10');

        $props = $this->actingAs($this->biuro())
            ->post('/pracownicy/'.$this->niemcy->id.'/create', [
                'start' => '2026-10-01', 'end' => '2026-10-31',
            ])
            ->viewData('page')['props'];

        $this->assertSame('Niemcy', $props['krajBudowy']);

        $kandydat = collect($props['contactsFree'])->concat($props['specialists'])
            ->firstWhere('id', $this->pracownik->id);

        $this->assertNotNull($kandydat['limit_183'] ?? null, 'Liczba ma stać przy nazwisku w chwili wyboru.');
    }

    public function test_przy_budowie_w_kraju_nie_ma_kolumny_limitu(): void
    {
        $props = $this->actingAs($this->biuro())
            ->post('/pracownicy/'.$this->polska->id.'/create', [
                'start' => '2026-10-01', 'end' => '2026-10-31',
            ])
            ->viewData('page')['props'];

        $this->assertNull($props['krajBudowy'], 'W kraju macierzystym limit nie biegnie.');
    }

    public function test_kraj_rozliczany_rocznie_zeruje_licznik_w_styczniu(): void
    {
        // Francja liczy rok podatkowy, więc grudniowy pobyt nie obciąża
        // stycznia. Przy liczeniu ruchomym obciążałby.
        $this->pobyt($this->francja, '2025-11-01', '2025-12-31');   // 61 dni
        $this->pobyt($this->francja, '2026-01-02', '2026-03-31');   // 89 dni

        $wiersz = $this->wyliczenie()['Francja'];

        $this->assertSame(89, $wiersz['dni_12m'], 'Liczy się tylko rok 2026.');
        $this->assertSame('rok 2026', $wiersz['okres']);
        $this->assertSame(89, $wiersz['najwieksze_okno'], 'Najcięższy rok, nie suma z dwóch.');
    }

    public function test_ten_sam_uklad_dni_daje_inny_wynik_zaleznie_od_kraju(): void
    {
        // Ta sama para pobytów przez przełom roku: w Niemczech sumuje się,
        // we Francji nie. To jest sedno uwagi Tomasza.
        $this->pobyt($this->niemcy, '2025-11-01', '2025-12-31');
        $this->pobyt($this->niemcy, '2026-01-02', '2026-03-31');
        $this->pobyt($this->francja, '2025-11-01', '2025-12-31');
        $this->pobyt($this->francja, '2026-01-02', '2026-03-31');

        $wynik = $this->wyliczenie();

        $this->assertSame(150, $wynik['Niemcy']['dni_12m'], 'Ruchome 12 miesięcy bierze oba pobyty.');
        $this->assertSame(89, $wynik['Francja']['dni_12m'], 'Rok podatkowy bierze tylko ten z 2026.');
    }

    public function test_przy_liczeniu_rocznym_limit_zwalnia_pierwszego_stycznia(): void
    {
        $this->pobyt($this->francja, '2026-01-02', '2026-09-15');

        $wiersz = $this->wyliczenie()['Francja'];

        $this->assertSame(0, $wiersz['pozostalo']);
        $this->assertSame('2027-01-01', $wiersz['wolne_od']);
    }

    public function test_zaklad_podatkowy_nie_wchodzi_do_limitu(): void
    {
        // Podatek należy się tam od pierwszego dnia, więc próg nie ma znaczenia.
        // Decyduje pole "Zakład podatkowy" z formularza budowy.
        $this->niemcy->update(['zaklad' => 'tak']);
        $this->pobyt($this->niemcy, '2026-01-06', '2026-09-15');

        $this->assertArrayNotHasKey('Niemcy', $this->wyliczenie());
    }

    public function test_budowa_w_polsce_jest_poza_limitem_niezaleznie_od_pola(): void
    {
        // Pytanie Tomasza: czy projekty w Polsce da się wyłączyć automatycznie.
        // Są wyłączone od początku — pole "Zakład podatkowy" niczego tu nie
        // zmienia, bo limit dotyczy tylko kontraktów zagranicznych.
        foreach ([null, 'nie', 'tak'] as $wartosc) {
            $this->polska->update(['zaklad' => $wartosc]);
            $this->pobyt($this->polska, '2026-01-06', '2026-09-15');

            $this->assertArrayNotHasKey('Polska', $this->wyliczenie(), 'Pole = '.var_export($wartosc, true));
            $this->assertFalse($this->polska->fresh()->liczySieDoLimitu183());

            ContactWorkDate::where('organization_id', $this->polska->id)->delete();
        }
    }

    public function test_przypisanie_na_budowe_w_polsce_nie_straszy(): void
    {
        $this->pobyt($this->polska, '2026-01-06', '2026-06-30');

        $this->actingAs($this->biuro())
            ->post('/contacts/'.$this->pracownik->id.'/przypisz-budowe', [
                'organization_id' => $this->polska->id,
                'start' => '2026-10-01',
                'end' => '2026-12-30',
            ])
            ->assertSessionHas('success')
            ->assertSessionMissing('warning');
    }

    public function test_przypisanie_na_zaklad_podatkowy_nie_straszy(): void
    {
        $this->niemcy->update(['zaklad' => 'tak']);
        $this->pobyt($this->niemcy, '2026-01-06', '2026-06-30');

        $this->actingAs($this->biuro())
            ->post('/contacts/'.$this->pracownik->id.'/przypisz-budowe', [
                'organization_id' => $this->niemcy->id,
                'start' => '2026-10-01',
                'end' => '2026-11-30',
            ])
            ->assertSessionHas('success')
            ->assertSessionMissing('warning');
    }

    public function test_dodanie_do_kierownictwa_tez_ostrzega(): void
    {
        // Kierownictwo jeździ za granicę tak samo jak montaż.
        $this->pobyt($this->niemcy, '2026-01-06', '2026-06-30');

        $this->actingAs($this->biuro())
            ->post('/budowy/'.$this->niemcy->id.'/kierownictwo', [
                'contact_id' => $this->pracownik->id,
                'start' => '2026-10-01',
                'end' => '2026-11-30',
            ])
            ->assertSessionHas('warning');
    }

    public function test_slownik_krajow_pozwala_zmienic_sposob_liczenia(): void
    {
        $admin = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'admin@mkl.pl',
            'owner' => 1, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
        $francja = KrajTyp::where('name', 'Francja')->first();

        $this->pobyt($this->francja, '2025-11-01', '2025-12-31');
        $this->pobyt($this->francja, '2026-01-02', '2026-03-31');

        $this->assertSame(89, $this->wyliczenie()['Francja']['dni_12m']);

        // Zmiana przepisów albo nowy kontrakt — biuro przestawia to samo.
        $this->actingAs($admin)
            ->put('/krajTyp/'.$francja->id, [
                'name' => 'Francja',
                'wymaga_a1' => true,
                'sposob_183' => KrajTyp::SPOSOB_12M,
            ])
            ->assertRedirect();

        $this->assertSame(150, $this->wyliczenie()['Francja']['dni_12m']);
    }

    public function test_kod_kraju_w_polu_zakladu_nie_wylacza_limitu(): void
    {
        // W tym polu bywają wpisane kody krajów (SE, PL, FI). Nie zgadujemy,
        // co autor miał na myśli — limit liczy się dalej.
        $this->niemcy->update(['zaklad' => 'SE']);
        $this->pobyt($this->niemcy, '2026-01-06', '2026-09-15');

        $this->assertArrayHasKey('Niemcy', $this->wyliczenie());
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
