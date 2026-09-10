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
 * Liczniki pracowników na pulpicie i na liście budów.
 *
 * Obie liczyły wszystkich razem — pracowników fizycznych i kierownictwo.
 * Kafelek "Pracownicy" pokazywał 187, a po kliknięciu lista miała 143, bo
 * kierownictwo ma osobną zakładkę. Na budowach to samo mieszało zarządzających
 * z montażem, choć kierownik i inżynier mają na tej liście własne kolumny.
 */
class LicznikPracownikowTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Organization $budowa;
    private Funkcja $monter;
    private Funkcja $kierownikBudowy;

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
            'account_id' => $this->accountId, 'nazwaBud' => 'Valmet Ortofta',
        ]);

        $this->monter = Funkcja::create([
            'name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false, 'rola_budowy' => null,
        ]);
        $this->kierownikBudowy = Funkcja::create([
            'name' => 'Kierownik Budowy', 'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
        ]);
    }

    private function pracownik(string $nazwisko, Funkcja $stanowisko, ?Organization $budowa = null): Contact
    {
        $osoba = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan',
            'last_name' => $nazwisko, 'funkcja_id' => $stanowisko->id,
        ]);

        if ($budowa) {
            ContactWorkDate::create([
                'contact_id' => $osoba->id,
                'organization_id' => $budowa->id,
                'start' => now()->subMonth()->toDateString(),
                'end' => null,
            ]);
        }

        return $osoba;
    }

    private function zalozKierownika(Organization ...$budowy): User
    {
        $osoba = $this->pracownik('Kierowniczak', $this->kierownikBudowy);

        $user = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kierownik'.uniqid().'@mkl.pl',
            'first_name' => $osoba->first_name, 'last_name' => $osoba->last_name,
            'owner' => Role::KIEROWNIK->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
        $osoba->update(['user_id' => $user->id]);

        foreach ($budowy as $budowa) {
            ContactWorkDate::create([
                'contact_id' => $osoba->id,
                'organization_id' => $budowa->id,
                'start' => now()->subMonth()->toDateString(),
                'end' => null,
            ]);
        }

        return $user;
    }

    private function pulpit(User $user): array
    {
        return $this->actingAs($user)->get('/')->viewData('page')['props']['stats'];
    }

    private function wierszBudowy(User $user, string $nazwa): array
    {
        $props = $this->actingAs($user)->get('/budowy')->viewData('page')['props'];

        return collect($props['organizations']['data'] ?? $props['organizations'])
            ->firstWhere('nazwaBud', $nazwa);
    }

    public function test_kolumna_pracownicy_na_budowie_pomija_kierownictwo(): void
    {
        $this->pracownik('Cebula', $this->monter, $this->budowa);
        $this->pracownik('Dudek', $this->monter, $this->budowa);
        $this->pracownik('Adamczyk', $this->kierownikBudowy, $this->budowa);

        $wiersz = $this->wierszBudowy($this->biuro, 'Valmet Ortofta');

        $this->assertSame(2, $wiersz['active_workers_count'], 'Kierownik ma własną kolumnę, nie doliczamy go do pracowników.');
        $this->assertSame(1, $wiersz['active_leaders_count']);
    }

    public function test_budowa_obsadzona_samym_kierownictwem_pokazuje_zero_pracownikow(): void
    {
        // Biuro Siedlce: same osoby zarządzające, żadnego montażu.
        $this->pracownik('Adamczyk', $this->kierownikBudowy, $this->budowa);

        $wiersz = $this->wierszBudowy($this->biuro, 'Valmet Ortofta');

        $this->assertSame(0, $wiersz['active_workers_count']);
        $this->assertSame(1, $wiersz['active_leaders_count'], 'Inaczej budowa wyglądałaby na pustą.');
    }

    public function test_pobyt_usunietego_pracownika_nie_podbija_licznika(): void
    {
        $this->pracownik('Cebula', $this->monter, $this->budowa);
        $this->pracownik('Zwolniony', $this->monter, $this->budowa)->delete();

        $this->assertSame(1, $this->wierszBudowy($this->biuro, 'Valmet Ortofta')['active_workers_count']);
    }

    public function test_kafelek_na_pulpicie_zgadza_sie_z_lista_pracownikow(): void
    {
        // Sedno zgłoszenia: liczba na kafelku i liczba na liście, do której
        // kafelek prowadzi, muszą być tą samą liczbą.
        $this->pracownik('Cebula', $this->monter);
        $this->pracownik('Dudek', $this->monter);
        $this->pracownik('Ewiak', $this->monter);
        $this->pracownik('Adamczyk', $this->kierownikBudowy);
        $this->pracownik('Bącik', $this->kierownikBudowy);

        $stats = $this->pulpit($this->biuro);

        $naLiscie = $this->actingAs($this->biuro)->get('/contacts')
            ->viewData('page')['props']['contacts']['total'];

        $this->assertSame(3, $stats['pracownicy']);
        $this->assertSame($naLiscie, $stats['pracownicy']);
        $this->assertSame(2, $stats['kierownictwo'], 'Kierownictwo nie znika, ma osobny podpis.');
    }

    public function test_osoba_bez_stanowiska_liczy_sie_jako_pracownik(): void
    {
        // Tak samo jak na liście — inaczej wypadłaby z obu liczników.
        Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan',
            'last_name' => 'Bezstanowiska', 'funkcja_id' => null,
        ]);

        $this->assertSame(1, $this->pulpit($this->biuro)['pracownicy']);
    }

    /**
     * Kierownik nie ma zakładki Pracownicy, więc kafelek prowadził donikąd —
     * był zwykłym prostokątem. Ma działać jak sąsiedni "Twoje budowy".
     */
    public function test_kafelek_kierownika_prowadzi_do_obsady_jego_budowy(): void
    {
        $kierownik = $this->zalozKierownika($this->budowa);

        $this->assertSame("/pracownicy/{$this->budowa->id}", $this->pulpit($kierownik)['pracownicy_adres']);

        $this->actingAs($kierownik)->get("/pracownicy/{$this->budowa->id}")->assertOk();
    }

    public function test_kierownik_kilku_budow_trafia_na_ich_liste(): void
    {
        // Jedna obsada by nie wystarczyła — kafelek sumuje kilka budów.
        $druga = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'AET Lestrem',
        ]);
        $kierownik = $this->zalozKierownika($this->budowa, $druga);

        $this->assertSame('/budowy', $this->pulpit($kierownik)['pracownicy_adres']);
    }

    public function test_kierownik_bez_budowy_nie_dostaje_odnosnika(): void
    {
        $kierownik = $this->zalozKierownika();

        $this->assertNull($this->pulpit($kierownik)['pracownicy_adres']);
    }

    public function test_biuro_trafia_z_kafelka_na_liste_pracownikow(): void
    {
        $this->assertSame('/contacts', $this->pulpit($this->biuro)['pracownicy_adres']);
    }

    /**
     * Liczba na kafelku i liczba w nagłówku obsady budowy muszą się zgadzać.
     * Nagłówek liczył też pobyty zaczynające się dopiero za tydzień.
     */
    public function test_pobyt_zaczynajacy_sie_w_przyszlosci_nie_jest_obecnoscia(): void
    {
        $kierownik = $this->zalozKierownika($this->budowa);
        $this->pracownik('Cebula', $this->monter, $this->budowa);

        $przyszly = $this->pracownik('Nowak', $this->monter);
        ContactWorkDate::create([
            'contact_id' => $przyszly->id,
            'organization_id' => $this->budowa->id,
            'start' => now()->addWeek()->toDateString(),
            'end' => now()->addMonths(2)->toDateString(),
        ]);

        $wpisy = $this->actingAs($kierownik)->get("/pracownicy/{$this->budowa->id}")
            ->viewData('page')['props']['contactworkdates']['data'];

        $obecni = collect($wpisy)->where('on_site', true);

        $this->assertCount(1, $obecni, 'Kto jeszcze nie przyjechał, nie jest na budowie.');
        $this->assertSame('Cebula', $obecni->first()['contact']['last_name']);
        $this->assertTrue(collect($wpisy)->firstWhere('contact.last_name', 'Nowak')['przyszly']);
        $this->assertSame($this->pulpit($kierownik)['pracownicy'], $obecni->count());
    }

    public function test_pulpit_kierownika_liczy_tylko_pracownikow_ze_swoich_budow(): void
    {
        $szef = $this->pracownik('Kierowniczak', $this->kierownikBudowy, $this->budowa);

        $kierownik = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kierownik@mkl.pl',
            'first_name' => $szef->first_name, 'last_name' => $szef->last_name,
            'owner' => Role::KIEROWNIK->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
        $szef->update(['user_id' => $kierownik->id]);

        $this->pracownik('Cebula', $this->monter, $this->budowa);

        $cudza = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Siemens Łódź',
        ]);
        $this->pracownik('Obcy', $this->monter, $cudza);

        $stats = $this->pulpit($kierownik);

        $this->assertSame(1, $stats['pracownicy'], 'Sam kierownik nie jest swoim pracownikiem.');
        $this->assertSame(1, $stats['kierownictwo']);
    }
}
