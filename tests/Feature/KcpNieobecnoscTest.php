<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Factory\BuildTimeShiftFactory;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\ShiftStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dzień KCP zakryty wpisem nieobecności.
 *
 * Kliknięcie w taki dzień nie robiło nic i wyglądało na usterkę: kierownik
 * nie miał jak poprawić urlopu, z którego pracownik wrócił wcześniej.
 * Poprawia się go przy nieobecności, nie w kratce KCP — ekran musi to
 * powiedzieć i pokazać drogę.
 */
class KcpNieobecnoscTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Organization $budowa;
    private Contact $pracownik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->budowa = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Lausitzer Zeitz',
        ]);

        $this->pracownik = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Monter',
        ]);

        ContactWorkDate::create([
            'contact_id' => $this->pracownik->id,
            'organization_id' => $this->budowa->id,
            'start' => '2026-09-01',
            'end' => '2026-09-30',
        ]);
    }

    /** @return array<int, object> dni pracownika w KCP za wrzesień */
    private function dni(): array
    {
        $shifts = (array) BuildTimeShiftFactory::create($this->budowa->id, '2026-09-15');

        return $shifts[$this->pracownik->id] ?? [];
    }

    public function test_dzien_z_nieobecnosci_mowi_skad_pochodzi(): void
    {
        $zwolnienie = ShiftStatus::create(['code' => 'ZL', 'title' => 'Zwolnienie Lekarskie']);

        Holiday::create([
            'contact_id' => $this->pracownik->id,
            'shift_status_id' => $zwolnienie->id,
            'start' => '2026-09-10',
            'end' => '2026-09-22',
        ]);

        $dzien = $this->dni()[15];

        $this->assertTrue($dzien->isBlocked);
        $this->assertSame('holiday', $dzien->blockedType);
        $this->assertSame('Zwolnienie Lekarskie', $dzien->blokada['rodzaj']);
        $this->assertSame('ZL', $dzien->blokada['kod']);
        $this->assertSame('2026-09-22', $dzien->blokada['do'], 'Po tej dacie kierownik wie, co skrócić.');
    }

    public function test_zwykly_dzien_nie_ma_zadnej_blokady(): void
    {
        $dzien = $this->dni()[15];

        $this->assertFalse($dzien->isBlocked);
        $this->assertNull($dzien->blokada);
    }

    public function test_skrocenie_nieobecnosci_odblokowuje_dzien(): void
    {
        // Pracownik wrócił wcześniej: kadry albo kierownik skracają wpis,
        // a KCP poprawia się samo.
        $zwolnienie = ShiftStatus::create(['code' => 'ZL', 'title' => 'Zwolnienie Lekarskie']);

        $wpis = Holiday::create([
            'contact_id' => $this->pracownik->id,
            'shift_status_id' => $zwolnienie->id,
            'start' => '2026-09-10',
            'end' => '2026-09-22',
        ]);

        // 21 września to poniedziałek — niedziela byłaby zablokowana
        // z całkiem innego powodu i test niczego by nie dowiódł.
        $this->assertTrue($this->dni()[21]->isBlocked);

        $wpis->update(['end' => '2026-09-18']);

        $this->assertFalse($this->dni()[21]->isBlocked, 'Dzień poza skróconą nieobecnością znów jest do wpisania.');
    }

    public function test_kcp_pokazuje_rzeczywisty_rodzaj_nieobecnosci(): void
    {
        // Szło na sztywno UW, więc komuś na zwolnieniu lekarskim wychodził
        // na KCP urlop wypoczynkowy — przy wypłacie to dwie różne rzeczy.
        ShiftStatus::create(['code' => 'UW', 'title' => 'Urlop Wypoczynkowy']);
        $zwolnienie = ShiftStatus::create(['code' => 'ZL', 'title' => 'Zwolnienie Lekarskie']);

        Holiday::create([
            'contact_id' => $this->pracownik->id,
            'shift_status_id' => $zwolnienie->id,
            'start' => '2026-09-14',
            'end' => '2026-09-18',
        ]);

        // 16 września to środa.
        $this->assertSame($zwolnienie->id, $this->dni()[16]->status);
    }

    public function test_sobota_w_srodku_nieobecnosci_zostaje_pusta(): void
    {
        // W tradycyjnym KCP nie wpisuje się nic, gdy pracownika nie ma.
        $zwolnienie = ShiftStatus::create(['code' => 'ZL', 'title' => 'Zwolnienie Lekarskie']);

        Holiday::create([
            'contact_id' => $this->pracownik->id,
            'shift_status_id' => $zwolnienie->id,
            'start' => '2026-09-14',
            'end' => '2026-09-25',
        ]);

        // 19 września to sobota, 18 to piątek.
        $this->assertNull($this->dni()[19]->status, 'Sobota zostaje pusta.');
        $this->assertSame($zwolnienie->id, $this->dni()[18]->status, 'Dzień roboczy dalej opisany.');
    }

    public function test_sobota_dalej_mowi_skad_blokada(): void
    {
        // Pusto nie znaczy "bez powodu" — po kliknięciu ma być wyjaśnienie.
        $zwolnienie = ShiftStatus::create(['code' => 'ZL', 'title' => 'Zwolnienie Lekarskie']);

        Holiday::create([
            'contact_id' => $this->pracownik->id,
            'shift_status_id' => $zwolnienie->id,
            'start' => '2026-09-14',
            'end' => '2026-09-25',
        ]);

        $sobota = $this->dni()[19];

        $this->assertSame('holiday', $sobota->blockedType);
        $this->assertSame('Zwolnienie Lekarskie', $sobota->blokada['rodzaj']);
    }

    public function test_nieobecnosc_bez_rodzaju_wpada_na_urlop_wypoczynkowy(): void
    {
        $uw = ShiftStatus::create(['code' => 'UW', 'title' => 'Urlop Wypoczynkowy']);

        Holiday::create([
            'contact_id' => $this->pracownik->id,
            'start' => '2026-09-14',
            'end' => '2026-09-18',
        ]);

        $this->assertSame($uw->id, $this->dni()[16]->status, 'Bez wskazanego rodzaju zostaje dawne zachowanie.');
    }

    public function test_dzien_bez_rodzaju_nieobecnosci_tez_da_sie_opisac(): void
    {
        Holiday::create([
            'contact_id' => $this->pracownik->id,
            'start' => '2026-09-10',
            'end' => '2026-09-12',
        ]);

        $this->assertSame('nieobecność', $this->dni()[11]->blokada['rodzaj']);
    }
}
