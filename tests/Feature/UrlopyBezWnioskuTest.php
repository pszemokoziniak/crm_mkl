<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\UrlopyBezWnioskuMail;
use App\Mail\ZgloszeniaKierownikowMail;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZgloszenieKierownika;
use App\Services\UrlopyBezWniosku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Urlop wpisany w KCP bez skanu wniosku: nie blokujemy zapisu, ale brak
 * widać w KCP, u kadr i na pulpicie kierownika, a po tygodniu idzie mail.
 */
class UrlopyBezWnioskuTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Organization $budowa;
    private Contact $pracownik;
    private User $kierownik;
    private int $uw;
    private int $zl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
        Funkcja::create(['id' => Funkcja::KIEROWNIK, 'name' => 'Kierownik Budowy', 'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK]);

        $this->uw = DB::table('shift_status')->insertGetId(['title' => 'Urlop Wypoczynkowy', 'code' => 'UW']);
        $this->zl = DB::table('shift_status')->insertGetId(['title' => 'Zwolnienie Lekarskie', 'code' => 'ZL']);

        $this->budowa = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Zeitz']);
        $this->pracownik = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski']);
        ContactWorkDate::create(['contact_id' => $this->pracownik->id, 'organization_id' => $this->budowa->id, 'start' => '2026-09-01', 'end' => null]);

        $this->kierownik = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kb@mkl.pl', 'owner' => 3, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
        $szef = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => 'Szef', 'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $this->kierownik->id]);
        ContactWorkDate::create(['contact_id' => $szef->id, 'organization_id' => $this->budowa->id, 'start' => '2026-09-01', 'end' => null]);
    }

    private function dzienKcp(string $dzien, int $status, ?Contact $kto = null): void
    {
        DB::table('building_time_sheets')->insert([
            'organization_id' => $this->budowa->id, 'contact_id' => ($kto ?? $this->pracownik)->id,
            'work_day' => $dzien, 'shift_status_id' => $status,
        ]);
    }

    public function test_sklejone_dni_urlopu_bez_wniosku_a_zwolnienie_nie_liczy_sie(): void
    {
        // Pn–pt, weekend, pn — jeden urlop; potem osobny dzień po przerwie.
        foreach (['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-10', '2026-09-11', '2026-09-14', '2026-09-23'] as $d) {
            $this->dzienKcp($d, $this->uw);
        }
        $this->dzienKcp('2026-09-16', $this->zl);

        $wynik = app(UrlopyBezWniosku::class)->dla([$this->budowa->id], '2026-09-01', '2026-09-30');

        $this->assertCount(2, $wynik);
        $this->assertSame(['Kowalski Jan', 'UW', '2026-09-07', '2026-09-14', 6], [$wynik[0]['pracownik'], $wynik[0]['kod'], $wynik[0]['od'], $wynik[0]['do'], $wynik[0]['dni']]);
        $this->assertSame(['2026-09-23', '2026-09-23', 1], [$wynik[1]['od'], $wynik[1]['do'], $wynik[1]['dni']]);
    }

    public function test_nieobecnosc_z_kartoteki_i_zgloszenie_ze_skanem_zakrywaja_dni(): void
    {
        foreach (['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-21', '2026-09-22'] as $d) {
            $this->dzienKcp($d, $this->uw);
        }
        // Kadry wstawiły nieobecność na 7–8.09 — 9.09 zostaje bez wniosku.
        Holiday::create(['contact_id' => $this->pracownik->id, 'start' => '2026-09-07', 'end' => '2026-09-08', 'shift_status_id' => $this->uw]);
        // Zgłoszenie urlopu ze skanem na 21–22.09 zakrywa oba dni; bez skanu nie zakrywałoby.
        $z = new ZgloszenieKierownika();
        $z->forceFill(['contact_id' => $this->pracownik->id, 'organization_id' => $this->budowa->id, 'user_id' => $this->kierownik->id,
            'rodzaj' => 'urlop', 'od' => '2026-09-21', 'do' => '2026-09-22', 'plik_sciezka' => 'zgloszenia/1/x.pdf', 'plik_nazwa' => 'x.pdf', 'status' => 'nowe'])->save();

        $wynik = app(UrlopyBezWniosku::class)->dla(null, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $wynik);
        $this->assertSame(['2026-09-09', '2026-09-09'], [$wynik[0]['od'], $wynik[0]['do']]);

        // Odrzucone zgłoszenie nie zakrywa.
        $z->forceFill(['status' => 'odrzucone'])->save();
        $this->assertCount(2, app(UrlopyBezWniosku::class)->dla(null, '2026-09-01', '2026-09-30'));
    }

    public function test_kcp_pulpit_i_kadry_pokazuja_braki(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-17'));
        $this->dzienKcp('2026-09-14', $this->uw);
        $this->dzienKcp('2026-09-15', $this->uw);

        $kcp = $this->actingAs($this->kierownik)->get('/building/'.$this->budowa->id.'/time-sheet?date=2026-09-01')->viewData('page')['props'];
        $this->assertCount(1, $kcp['urlopyBezWniosku']);
        $this->assertSame('Kowalski Jan', $kcp['urlopyBezWniosku'][0]['pracownik']);
        $this->assertArrayHasKey('urlop', $kcp['rodzajeZgloszen']);

        $pulpit = $this->actingAs($this->kierownik)->get('/')->viewData('page')['props'];
        $this->assertCount(1, $pulpit['urlopy_bez_wniosku']);

        $kadry = User::factory()->create(['account_id' => $this->accountId, 'email' => 'kadry@mkl.pl', 'owner' => 6, 'active' => 1, 'password_changed_at' => now()->toDateTimeString()]);
        $strona = $this->actingAs($kadry)->get('/zmiany-kadrowe')->viewData('page')['props'];
        $this->assertCount(1, $strona['urlopy_bez_wniosku']);
        $this->assertSame('Zeitz', $strona['urlopy_bez_wniosku'][0]['budowa']);
    }

    public function test_przypomnienie_idzie_do_kierownika_budowy_po_tygodniu(): void
    {
        Mail::fake();
        $this->travelTo(\Carbon\Carbon::parse('2026-09-17'));
        $this->dzienKcp('2026-09-08', $this->uw);   // 9 dni temu — przypominamy
        $inna = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Inna']);
        $obcy = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Ola', 'last_name' => 'Obca']);
        DB::table('building_time_sheets')->insert(['organization_id' => $inna->id, 'contact_id' => $obcy->id, 'work_day' => '2026-09-01', 'shift_status_id' => $this->uw]);

        $this->artisan('kadry:przypomnij-o-wnioskach')->assertExitCode(0);

        Mail::assertSent(UrlopyBezWnioskuMail::class, function (UrlopyBezWnioskuMail $m) {
            return $m->hasTo('kb@mkl.pl') && $m->urlopy->count() === 1 && $m->urlopy[0]['pracownik'] === 'Kowalski Jan';
        });
        Mail::assertSent(UrlopyBezWnioskuMail::class, 1);

        // Świeży brak (3 dni) nie dostaje jeszcze przypomnienia.
        DB::table('building_time_sheets')->delete();
        $this->dzienKcp('2026-09-14', $this->uw);
        Mail::fake();
        $this->artisan('kadry:przypomnij-o-wnioskach');
        Mail::assertNothingSent();
    }

    public function test_zgloszenia_ida_mailem_do_odbiorcow_powiadomien_kadrowych_raz(): void
    {
        Mail::fake();
        $odbiorca = User::factory()->create(['account_id' => $this->accountId, 'email' => 'mail@mkl.pl', 'owner' => 6, 'active' => 1, 'powiadomienia_kadrowe' => true, 'password_changed_at' => now()->toDateTimeString()]);
        User::factory()->create(['account_id' => $this->accountId, 'email' => 'bez@mkl.pl', 'owner' => 6, 'active' => 1, 'powiadomienia_kadrowe' => false, 'password_changed_at' => now()->toDateTimeString()]);

        $z = new ZgloszenieKierownika();
        $z->forceFill(['contact_id' => $this->pracownik->id, 'organization_id' => $this->budowa->id, 'user_id' => $this->kierownik->id, 'rodzaj' => 'zjazd', 'status' => 'nowe'])->save();

        $this->artisan('kadry:powiadom-mailem');
        Mail::assertSent(ZgloszeniaKierownikowMail::class, fn ($m) => $m->hasTo('mail@mkl.pl') && $m->zgloszenia->count() === 1);
        Mail::assertNotSent(ZgloszeniaKierownikowMail::class, fn ($m) => $m->hasTo('bez@mkl.pl'));
        $this->assertNotNull($z->fresh()->mail_wyslany_at);

        Mail::fake();
        $this->artisan('kadry:powiadom-mailem');
        Mail::assertNothingSent();
    }
}
