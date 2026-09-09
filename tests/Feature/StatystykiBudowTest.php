<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\BuildingTimeSheet;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\ShiftStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Statystyki budów liczone z Karty Czasu Pracy.
 *
 * O tym, czy status to urlop czy zwolnienie, decyduje kategoria ze słownika,
 * a nie nazwa ani numer — słownik prowadzi biuro i bywa w nim wszystko.
 */
class StatystykiBudowTest extends TestCase
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

    private function pracownik(string $nazwisko): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => $nazwisko,
        ]);
    }

    private function wpis(Contact $kto, string $dzien, string $efektywne, ?int $statusId = null, string $od = '07:00', string $do = '17:00'): void
    {
        BuildingTimeSheet::create([
            'organization_id' => $this->budowa->id,
            'contact_id' => $kto->id,
            'shift_status_id' => $statusId,
            'work_day' => $dzien.' 00:00:00',
            'work_from' => $dzien.' '.$od.':00',
            'work_to' => $dzien.' '.$do.':00',
            'effective_work_time' => $efektywne,
        ]);
    }

    private function status(string $tytul, ?string $kategoria): ShiftStatus
    {
        return ShiftStatus::create(['title' => $tytul, 'code' => substr($tytul, 0, 4), 'kategoria' => $kategoria]);
    }

    private function wiersz(array $parametry = []): ?array
    {
        $adres = '/statystyki'.($parametry ? '?'.http_build_query($parametry) : '');

        return collect($this->actingAs($this->biuro)->get($adres)->assertOk()->viewData('page')['props']['budowy'])
            ->firstWhere('nazwa', 'Lausitzer Zeitz');
    }

    public function test_sumuje_roboczogodziny_urlopy_i_zwolnienia(): void
    {
        $urlop = $this->status('Urlop wypoczynkowy', ShiftStatus::KAT_URLOP);
        $chore = $this->status('Zwolnienie lekarskie', ShiftStatus::KAT_ZWOLNIENIE);
        $nieob = $this->status('Nieobecność nieusprawiedliwiona', ShiftStatus::KAT_NIEOBECNOSC);

        $kowalski = $this->pracownik('Kowalski');
        $this->wpis($kowalski, '2026-03-02', '09:30');            // praca
        $this->wpis($kowalski, '2026-03-03', '08:00');            // praca
        $this->wpis($kowalski, '2026-03-04', '08:00', $urlop->id);
        $this->wpis($kowalski, '2026-03-05', '08:00', $chore->id);
        $this->wpis($kowalski, '2026-03-06', '08:00', $nieob->id);

        $b = $this->wiersz();

        $this->assertSame(17.5, $b['godziny']['praca']);
        $this->assertSame(8.0, $b['godziny']['urlop']);
        $this->assertSame(8.0, $b['godziny']['zwolnienie']);
        $this->assertSame(8.0, $b['godziny']['nieobecnosc']);
    }

    public function test_przerwy_to_roznica_miedzy_zmiana_a_czasem_efektywnym(): void
    {
        // Okno 07:00–17:00 to 10 godzin, efektywnie 9:30 — pół godziny przerwy.
        $this->wpis($this->pracownik('Nowak'), '2026-03-02', '09:30');

        $this->assertSame(0.5, $this->wiersz()['przerwy']);
    }

    public function test_status_bez_kategorii_wpada_do_innych(): void
    {
        // Biuro dopisuje statusy swobodnie; nieprzypisany nie może cicho
        // zasilić roboczogodzin ani urlopów.
        $dziwny = $this->status('Sraczka', null);
        $this->wpis($this->pracownik('Nowak'), '2026-03-02', '08:00', $dziwny->id);

        $b = $this->wiersz();

        $this->assertSame(8.0, $b['godziny']['inne']);
        $this->assertSame(0.0, $b['godziny']['praca']);

        $props = $this->actingAs($this->biuro)->get('/statystyki')->viewData('page')['props'];
        $this->assertContains('Sraczka', $props['statusyBezKategorii'], 'Ekran ma o tym powiedzieć wprost.');
    }

    public function test_licznik_osob_dniowek_i_sredniej(): void
    {
        $a = $this->pracownik('Kowalski');
        $b = $this->pracownik('Nowak');
        $this->wpis($a, '2026-03-02', '10:00');
        $this->wpis($a, '2026-03-03', '08:00');
        $this->wpis($b, '2026-03-02', '09:00');

        $w = $this->wiersz();

        $this->assertSame(2, $w['pracownikow']);
        $this->assertSame(3, $w['dni_pracy']);
        $this->assertSame(9.0, $w['srednia_dniowka']);
    }

    public function test_filtr_roku_zawęża_okres(): void
    {
        $kowalski = $this->pracownik('Kowalski');
        $this->wpis($kowalski, '2025-03-02', '08:00');
        $this->wpis($kowalski, '2026-03-02', '10:00');

        $this->assertSame(18.0, $this->wiersz()['godziny']['praca'], 'Bez filtra liczymy wszystko.');
        $this->assertSame(10.0, $this->wiersz(['rok' => '2026'])['godziny']['praca']);
        $this->assertSame(8.0, $this->wiersz(['rok' => '2025'])['godziny']['praca']);
    }

    public function test_zakonczone_budowy_wchodza_do_zestawienia(): void
    {
        // Prawie cała historia godzin należy do budów już zamkniętych —
        // bez nich zestawienie nie pokazuje niczego sensownego.
        $this->wpis($this->pracownik('Kowalski'), '2026-03-02', '08:00');
        $this->budowa->delete();

        $wiersz = $this->wiersz();
        $this->assertNotNull($wiersz, 'Zakończona budowa zostaje w zestawieniu.');
        $this->assertTrue($wiersz['archiwum']);
        $this->assertSame(8.0, $wiersz['godziny']['praca']);

        // …ale da się zawęzić do trwających.
        $this->assertNull($this->wiersz(['zakres' => 'aktywne']));
    }

    public function test_kierownik_widzi_tylko_swoje_budowy(): void
    {
        $cudza = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Cudza budowa']);

        $stanowisko = Funkcja::create([
            'name' => 'Kierownik Budowy', 'kierownictwo' => true,
            'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
        ]);
        $kierownikUser = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kier@mkl.pl',
            'first_name' => 'Adam', 'last_name' => 'Kierowniczak',
            'owner' => Role::KIEROWNIK->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
        $kontakt = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => 'Kierowniczak',
            'funkcja_id' => $stanowisko->id, 'user_id' => $kierownikUser->id,
        ]);
        ContactWorkDate::create([
            'contact_id' => $kontakt->id, 'organization_id' => $this->budowa->id,
            'start' => now()->subMonth()->toDateString(), 'end' => null,
        ]);

        $this->wpis($this->pracownik('Kowalski'), '2026-03-02', '08:00');

        $nazwy = collect($this->actingAs($kierownikUser)->get('/statystyki')->assertOk()
            ->viewData('page')['props']['budowy'])->pluck('nazwa');

        $this->assertContains('Lausitzer Zeitz', $nazwy);
        $this->assertNotContains('Cudza budowa', $nazwy);
    }
}
