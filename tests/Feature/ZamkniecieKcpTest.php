<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Controllers\BuildingTimeSheet;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\PobranieKcp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Uzupełnianie KCP przez kierownika budowy: okno siedmiu dni i zamknięcie
 * miesiąca po tym, jak kadry pobiorą KCP.
 *
 * Reguła "kilku dni wstecz" istniała dotąd tylko w przeglądarce i liczyła
 * dni od początku wyświetlanego miesiąca, nie od konkretnego dnia. Samo
 * wysłanie żądania omijało ją w całości.
 */
class ZamkniecieKcpTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Organization $budowa;
    private User $kierownik;
    private User $kadry;
    private User $biuro;
    private Contact $pracownik;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05');

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        Funkcja::create([
            'id' => Funkcja::KIEROWNIK, 'name' => 'Kierownik Budowy',
            'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
        ]);

        $this->budowa = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Lausitzer Zeitz',
        ]);

        $this->kierownik = $this->konto('kierownik@mkl.pl', Role::KIEROWNIK->value);
        $this->kadry = $this->konto('kadry@mkl.pl', Role::KADRY->value);
        $this->biuro = $this->konto('biuro@mkl.pl', Role::BIURO->value);

        // Kierownik musi być w kierownictwie budowy, żeby w ogóle tam wejść.
        $szef = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => 'Kierowniczak',
            'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $this->kierownik->id,
        ]);
        ContactWorkDate::create([
            'contact_id' => $szef->id, 'organization_id' => $this->budowa->id,
            'start' => '2026-01-01', 'end' => null,
        ]);

        $this->pracownik = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Monter',
        ]);
        ContactWorkDate::create([
            'contact_id' => $this->pracownik->id, 'organization_id' => $this->budowa->id,
            'start' => '2026-01-01', 'end' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function konto(string $email, int $owner): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => $email,
            'first_name' => 'Ktoś', 'last_name' => ucfirst(explode('@', $email)[0]),
            'owner' => $owner, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function wpisz(User $kto, string $dzien)
    {
        return $this->actingAs($kto)->postJson('/building/'.$this->budowa->id.'/time-sheet', [
            'build' => $this->budowa->id,
            'id' => $this->pracownik->id,
            'day' => $dzien,
            'from' => ['hours' => 7, 'minutes' => 0],
            'to' => ['hours' => 15, 'minutes' => 0],
            'workTime' => ['hours' => 8, 'minutes' => 0],
        ]);
    }

    private function pobierzKcp(User $kto, string $miesiac)
    {
        return $this->actingAs($kto)
            ->get('/building/'.$this->budowa->id.'/time-sheet/export?date='.$miesiac.'-15');
    }

    public function test_kierownik_uzupelnia_siedem_dni_wstecz(): void
    {
        $this->wpisz($this->kierownik, '2026-10-05')->assertOk();
        $this->wpisz($this->kierownik, Carbon::today()->subDays(7)->toDateString())->assertOk();
    }

    public function test_starszy_dzien_kierownikowi_juz_nie_przechodzi(): void
    {
        $odpowiedz = $this->wpisz($this->kierownik, Carbon::today()->subDays(8)->toDateString());

        $odpowiedz->assertForbidden();
        $this->assertStringContainsString('7 dni wstecz', $odpowiedz->json('message'));
        $this->assertDatabaseCount('building_time_sheets', 0);
    }

    public function test_biuro_nie_ma_okna_siedmiu_dni(): void
    {
        // Poprawki po terminie to rola biura, nie kierownika.
        $this->wpisz($this->biuro, '2026-01-15')->assertOk();
    }

    public function test_pobranie_w_trakcie_miesiaca_niczego_nie_zamyka(): void
    {
        // Kierownik ma cały miesiąc na uzupełnianie.
        Carbon::setTestNow('2026-10-20');

        $this->pobierzKcp($this->kadry, '2026-10')->assertOk();

        $this->assertFalse(PobranieKcp::czyZamkniete($this->budowa->id, Carbon::parse('2026-10-15')));
        $this->wpisz($this->kierownik, '2026-10-19')->assertOk();
    }

    public function test_pobranie_w_kolejnym_miesiacu_zamyka_poprzedni(): void
    {
        // Sedno zgłoszenia: 2 października kadry biorą wrzesień do wypłat.
        $this->pobierzKcp($this->kadry, '2026-09')->assertOk();

        $this->assertTrue(PobranieKcp::czyZamkniete($this->budowa->id, Carbon::parse('2026-09-15')));

        $odpowiedz = $this->wpisz($this->kierownik, '2026-09-30');
        $odpowiedz->assertForbidden();
        $this->assertStringContainsString('zamknięte', $odpowiedz->json('message'));
    }

    public function test_zamkniecie_dotyczy_tylko_tego_miesiaca_i_tej_budowy(): void
    {
        $this->pobierzKcp($this->kadry, '2026-09');

        // Bieżący miesiąc zostaje otwarty.
        $this->wpisz($this->kierownik, '2026-10-02')->assertOk();

        $inna = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Inna budowa']);
        $this->assertFalse(PobranieKcp::czyZamkniete($inna->id, Carbon::parse('2026-09-15')));
    }

    public function test_pobranie_przez_biuro_nie_zamyka(): void
    {
        // Zamyka tylko pobranie przez kadry — biuro zagląda do KCP na co dzień.
        $this->pobierzKcp($this->biuro, '2026-09')->assertOk();

        $this->assertFalse(PobranieKcp::czyZamkniete($this->budowa->id, Carbon::parse('2026-09-15')));
    }

    public function test_biuro_poprawia_takze_po_zamknieciu(): void
    {
        $this->pobierzKcp($this->kadry, '2026-09');

        $this->wpisz($this->biuro, '2026-09-30')->assertOk();
    }

    public function test_liczy_sie_pierwsze_pobranie(): void
    {
        $this->pobierzKcp($this->kadry, '2026-09');
        $pierwsze = PobranieKcp::firstOrFail();

        Carbon::setTestNow('2026-11-10');
        $this->pobierzKcp($this->kadry, '2026-09');

        $this->assertSame(1, PobranieKcp::count());
        $this->assertEquals($pierwsze->created_at, PobranieKcp::firstOrFail()->created_at);
    }

    public function test_ekran_mowi_ze_miesiac_jest_zamkniety(): void
    {
        $this->pobierzKcp($this->kadry, '2026-09');

        $props = $this->actingAs($this->kierownik)
            ->get('/building/'.$this->budowa->id.'/time-sheet?date=2026-09-15')
            ->viewData('page')['props'];

        $this->assertSame('09.2026', $props['zamkniety']['okres']);
        $this->assertSame('Kadry Ktoś', $props['zamkniety']['kto']);
        $this->assertSame(BuildingTimeSheet::DNI_WSTECZ_KIEROWNIK, $props['dniWstecz']);
    }

    public function test_usuwanie_dnia_tez_jest_zamkniete(): void
    {
        DB::table('building_time_sheets')->insert([
            'organization_id' => $this->budowa->id,
            'contact_id' => $this->pracownik->id,
            'work_day' => '2026-09-30',
            'effective_work_time' => '08:00',
        ]);

        $this->pobierzKcp($this->kadry, '2026-09');

        $this->actingAs($this->kierownik)
            ->post('/building/'.$this->budowa->id.'/time-sheet/delete', [
                'build' => $this->budowa->id,
                'id' => $this->pracownik->id,
                'day' => '2026-09-30',
                'from' => ['hours' => 7, 'minutes' => 0],
                'to' => ['hours' => 15, 'minutes' => 0],
                'workTime' => ['hours' => 8, 'minutes' => 0],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('building_time_sheets', 1);
    }
}
