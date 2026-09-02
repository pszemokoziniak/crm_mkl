<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\ShiftStatus;
use App\Models\User;
use App\Services\StatusPracownika;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Nieobecności pracownika (urlop, zwolnienie) — zamiast gołego "Nie pracuje".
 */
class NieobecnosciTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private int $accountId;
    private int $urlopId;
    private int $zwolnienieId;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 20));

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro@example.com',
            'owner' => 2,
            'active' => 1,
        ]);

        $this->urlopId = ShiftStatus::create(['title' => 'Urlop wypoczynkowy', 'code' => 'UW'])->id;
        $this->zwolnienieId = ShiftStatus::create(['title' => 'Zwolnienie lekarskie', 'code' => 'ZL'])->id;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_pracownik_na_urlopie_ma_powod_zamiast_nie_pracuje(): void
    {
        $pracownik = $this->pracownik();
        $this->nieobecnosc($pracownik, $this->urlopId, '2026-09-14', '2026-09-30');

        $status = app(StatusPracownika::class)->dla($pracownik);

        $this->assertSame('nieobecnosc', $status['typ']);
        $this->assertSame('Urlop wypoczynkowy', $status['label']);
        $this->assertSame('2026-09-30', $status['do']);
    }

    public function test_bez_budowy_i_bez_nieobecnosci_zostaje_nie_pracuje(): void
    {
        $status = app(StatusPracownika::class)->dla($this->pracownik());

        $this->assertSame('brak', $status['typ']);
        $this->assertSame('Nie pracuje', $status['label']);
    }

    public function test_na_budowie_bez_nieobecnosci_pokazuje_budowe(): void
    {
        $pracownik = $this->pracownik();
        $this->pobyt($pracownik, '2026-09-01', '2026-10-31');

        $status = app(StatusPracownika::class)->dla($pracownik);

        $this->assertSame('budowa', $status['typ']);
        $this->assertSame('434_Valmet', $status['label']);
    }

    public function test_nieobecnosc_wygrywa_ale_budowa_zostaje_widoczna(): void
    {
        $pracownik = $this->pracownik();
        $this->pobyt($pracownik, '2026-09-01', '2026-10-31');
        $this->nieobecnosc($pracownik, $this->zwolnienieId, '2026-09-15', '2026-09-25');

        $status = app(StatusPracownika::class)->dla($pracownik);

        $this->assertSame('nieobecnosc', $status['typ']);
        $this->assertSame('Zwolnienie lekarskie', $status['label']);
        // Zwolnienie nie zdejmuje pracownika z budowy.
        $this->assertSame('434_Valmet', $status['budowa']);
        $this->assertDatabaseHas('contact_work_dates', ['contact_id' => $pracownik->id]);
    }

    public function test_nieobecnosc_z_przeszlosci_nie_wplywa_na_dzis(): void
    {
        $pracownik = $this->pracownik();
        $this->nieobecnosc($pracownik, $this->urlopId, '2026-01-10', '2026-01-20');

        $this->assertSame('brak', app(StatusPracownika::class)->dla($pracownik)['typ']);
    }

    public function test_lista_pracownikow_zwraca_status_i_nie_mnozy_zapytan(): void
    {
        foreach (range(1, 5) as $i) {
            $pracownik = $this->pracownik('Nowak'.$i);
            $this->nieobecnosc($pracownik, $this->urlopId, '2026-09-14', '2026-09-30');
        }

        $zapytania = 0;
        DB::listen(function () use (&$zapytania) {
            $zapytania++;
        });

        $this->actingAs($this->biuro)
            ->get('/contacts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('contacts.data.0.pracuje.typ', 'nieobecnosc')
                ->where('contacts.data.0.pracuje.label', 'Urlop wypoczynkowy')
            );

        $this->assertLessThan(15, $zapytania, "Za dużo zapytań: {$zapytania}");
    }

    public function test_karta_pracownika_podaje_status(): void
    {
        $pracownik = $this->pracownik();
        $this->nieobecnosc($pracownik, $this->urlopId, '2026-09-14', '2026-09-30');

        $this->actingAs($this->biuro)
            ->get('/contacts/'.$pracownik->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('status.label', 'Urlop wypoczynkowy'));
    }

    public function test_zapis_nieobecnosci_wymaga_powodu(): void
    {
        $pracownik = $this->pracownik();

        $this->actingAs($this->biuro)
            ->post('/holiday/'.$pracownik->id, [
                'contact_id' => $pracownik->id,
                'start' => '2026-10-01',
                'end' => '2026-10-10',
            ])
            ->assertSessionHasErrors('shift_status_id');

        $this->assertSame(0, Holiday::count());
    }

    public function test_zapis_nieobecnosci_z_powodem_przechodzi(): void
    {
        $pracownik = $this->pracownik();

        $this->actingAs($this->biuro)
            ->post('/holiday/'.$pracownik->id, [
                'contact_id' => $pracownik->id,
                'shift_status_id' => $this->zwolnienieId,
                'start' => '2026-10-01',
                'end' => '2026-10-10',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('holidays', [
            'contact_id' => $pracownik->id,
            'shift_status_id' => $this->zwolnienieId,
        ]);
    }

    public function test_kolumna_pokazuje_koniec_obecnego_pobytu(): void
    {
        $pracownik = $this->pracownik();
        $this->pobyt($pracownik, '2026-09-01', '2026-10-31');

        $status = app(StatusPracownika::class)->dla($pracownik);

        $this->assertSame('2026-10-31', $status['budowa_do']);
        $this->assertNull($status['ostatni_pobyt_do']);
    }

    public function test_bez_obecnego_pobytu_kolumna_podaje_ostatni_zakonczony(): void
    {
        $pracownik = $this->pracownik();
        $this->pobyt($pracownik, '2024-05-06', '2024-07-20');
        $this->pobyt($pracownik, '2024-08-01', '2024-09-30');

        $status = app(StatusPracownika::class)->dla($pracownik);

        $this->assertNull($status['budowa_do']);
        // Najpóźniej zakończony, nie pierwszy z brzegu.
        $this->assertSame('2024-09-30', $status['ostatni_pobyt_do']);
    }

    public function test_urlop_nie_zasłania_daty_konca_pobytu_na_budowie(): void
    {
        $pracownik = $this->pracownik();
        $this->pobyt($pracownik, '2026-09-01', '2026-10-31');
        $this->nieobecnosc($pracownik, $this->urlopId, '2026-09-15', '2026-09-25');

        $status = app(StatusPracownika::class)->dla($pracownik);

        // Plakietka mówi o urlopie, kolumna o budowie — dwie różne daty.
        $this->assertSame('2026-09-25', $status['do']);
        $this->assertSame('2026-10-31', $status['budowa_do']);
    }

    public function test_pracownik_nigdy_nieprzypisany_ma_puste_daty(): void
    {
        $status = app(StatusPracownika::class)->dla($this->pracownik());

        $this->assertNull($status['budowa_do']);
        $this->assertNull($status['ostatni_pobyt_do']);
    }

    private function pracownik(string $nazwisko = 'Adamczyk'): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Marcin',
            'last_name' => $nazwisko,
        ]);
    }

    private function pobyt(Contact $pracownik, string $start, string $end): ContactWorkDate
    {
        $budowa = Organization::firstOrCreate(
            ['nazwaBud' => '434_Valmet'],
            ['account_id' => 0, 'name' => 'Valmet']
        );

        return ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $budowa->id,
            'start' => $start,
            'end' => $end,
        ]);
    }

    private function nieobecnosc(Contact $pracownik, int $powodId, string $start, string $end): Holiday
    {
        return Holiday::create([
            'contact_id' => $pracownik->id,
            'shift_status_id' => $powodId,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
