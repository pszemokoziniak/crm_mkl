<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Koszt;
use App\Models\KursWaluty;
use App\Models\Organization;
use App\Models\TypKosztu;
use App\Models\User;
use App\Services\PodzialKosztow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Koszty podróży i kwater: kurs NBP z dnia kosztu, podział kosztu budowy
 * na ludzi (osobodoby w pokoju, dni pobytu, wskazane osoby), pilnowanie
 * miejsc w pokoju i zakres dostępu kierownika projektu.
 */
class KosztyTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Organization $budowa;
    private Organization $inna;
    private User $biuro;
    private User $kp;
    private User $kb;
    private Contact $a;
    private Contact $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        Funkcja::create(['id' => Funkcja::KIEROWNIK, 'name' => 'Kierownik Budowy', 'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK]);

        $this->budowa = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Lausitz']);
        $this->inna = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Inna']);

        $this->biuro = $this->user(2, 'biuro@mkl.pl');
        $this->kb = $this->user(3, 'kb@mkl.pl');
        $this->kp = $this->user(5, 'kp@mkl.pl');

        // Kierownik budowy bez pobytu na budowie: podział po dniach pobytu
        // bierze każdego, kto na niej jest (kierownictwo też), a ten test
        // liczy udziały tylko A i B.
        Contact::create(['account_id' => $this->accountId, 'first_name' => 'Karol', 'last_name' => 'Budowy', 'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $this->kb->id]);

        $kpContact = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Piotr', 'last_name' => 'Projektu', 'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $this->kp->id]);
        $this->budowa->update(['kierownik_projektu_id' => $kpContact->id]);

        // A cały wrzesień na budowie, B od 21 września (10 dni).
        $this->a = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => 'Alfa']);
        $this->b = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Bartek', 'last_name' => 'Beta']);
        ContactWorkDate::create(['contact_id' => $this->a->id, 'organization_id' => $this->budowa->id, 'start' => '2026-09-01', 'end' => null]);
        ContactWorkDate::create(['contact_id' => $this->b->id, 'organization_id' => $this->budowa->id, 'start' => '2026-09-21', 'end' => '2026-09-30']);
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => $email, 'owner' => $owner,
            'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function typ(string $nazwa): TypKosztu
    {
        return TypKosztu::where('nazwa', $nazwa)->firstOrFail();
    }

    private function koszt(array $a): Koszt
    {
        $typ = $this->typ($a['typ'] ?? 'Paliwo i opłaty drogowe');
        unset($a['typ']);

        return Koszt::create(array_merge([
            'typ_kosztu_id' => $typ->id, 'organization_id' => $this->budowa->id, 'contact_id' => null,
            'data' => '2026-09-10', 'kwota' => 100, 'waluta' => 'PLN', 'kurs' => 1, 'kwota_pln' => 100,
            'dzielony' => $typ->dzielony, 'user_id' => $this->biuro->id,
        ], $a));
    }

    public function test_slownik_ma_piec_typow_na_start(): void
    {
        $this->assertSame(5, TypKosztu::count());
        $this->assertTrue($this->typ('Kwatera / pokój')->nocleg);
        $this->assertFalse($this->typ('Bilet lotniczy')->dzielony);
    }

    public function test_koszt_w_eur_dostaje_kurs_nbp_z_dnia_i_kwote_w_pln(): void
    {
        Http::fake(['api.nbp.pl/*' => Http::response(['rates' => [
            ['effectiveDate' => '2026-09-14', 'mid' => 4.30],
            ['effectiveDate' => '2026-09-15', 'mid' => 4.35],
        ]])]);

        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/koszty', [
            'typ_kosztu_id' => $this->typ('Bilet lotniczy')->id, 'data' => '2026-09-15',
            'kwota' => 100, 'waluta' => 'EUR', 'contact_id' => $this->a->id, 'opis' => 'Lot',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $koszt = Koszt::firstOrFail();
        $this->assertSame(4.35, $koszt->kurs);
        $this->assertSame(435.0, $koszt->kwota_pln);
        $this->assertFalse($koszt->kurs_reczny);
        $this->assertSame(2, KursWaluty::count());

        // Drugi koszt tego dnia bierze kurs z bazy — NBP pytamy raz.
        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/koszty', [
            'typ_kosztu_id' => $this->typ('Bilet lotniczy')->id, 'data' => '2026-09-15',
            'kwota' => 10, 'waluta' => 'EUR', 'contact_id' => $this->b->id,
        ])->assertSessionHasNoErrors();

        Http::assertSentCount(1);
    }

    public function test_weekend_bierze_ostatni_kurs_roboczy(): void
    {
        Http::fake(['api.nbp.pl/*' => Http::response(['rates' => [
            ['effectiveDate' => '2026-09-17', 'mid' => 4.20],
            ['effectiveDate' => '2026-09-18', 'mid' => 4.25],
        ]])]);

        // 19.09.2026 to sobota.
        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/koszty', [
            'typ_kosztu_id' => $this->typ('Bilet lotniczy')->id, 'data' => '2026-09-19',
            'kwota' => 100, 'waluta' => 'CHF', 'contact_id' => $this->a->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(4.25, Koszt::firstOrFail()->kurs);
    }

    public function test_bez_kursu_nbp_trzeba_wpisac_kurs_recznie(): void
    {
        Http::fake(['api.nbp.pl/*' => Http::response('', 404)]);
        $dane = [
            'typ_kosztu_id' => $this->typ('Bilet lotniczy')->id, 'data' => '2026-09-15',
            'kwota' => 100, 'waluta' => 'GBP', 'contact_id' => $this->a->id,
        ];

        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/koszty', $dane)
            ->assertSessionHasErrors('kurs');
        $this->assertSame(0, Koszt::count());

        $this->actingAs($this->biuro)->post('/budowy/'.$this->budowa->id.'/koszty', $dane + ['kurs_reczny' => 1, 'kurs' => 5.5])
            ->assertSessionHasNoErrors();

        $koszt = Koszt::firstOrFail();
        $this->assertTrue($koszt->kurs_reczny);
        $this->assertSame(550.0, $koszt->kwota_pln);
    }

    public function test_pokoj_dzieli_sie_po_osobodobach(): void
    {
        $pokoj = $this->koszt(['typ' => 'Kwatera / pokój', 'kwota' => 3000, 'kwota_pln' => 3000, 'od' => '2026-09-01', 'do' => '2026-09-30', 'miejsc' => 3, 'dzielony' => true]);
        $pokoj->osoby()->createMany([
            ['contact_id' => $this->a->id, 'od' => '2026-09-01', 'do' => '2026-09-30'],
            ['contact_id' => $this->b->id, 'od' => '2026-09-16', 'do' => '2026-09-30'],
        ]);

        $podzial = app(PodzialKosztow::class)->dlaBudowy($this->budowa, '2026-09');

        $this->assertSame(2000.0, $podzial->firstWhere('contact_id', $this->a->id)['kwota_pln']);
        $this->assertSame(1000.0, $podzial->firstWhere('contact_id', $this->b->id)['kwota_pln']);
        $this->assertSame('osobodoby w pokoju', $podzial->firstWhere('contact_id', $this->a->id)['pozycje'][0]['sposob']);
    }

    public function test_koszt_budowy_dzieli_sie_po_dniach_pobytu_w_miesiacu(): void
    {
        $this->koszt(['kwota' => 300, 'kwota_pln' => 300]); // A: 30 dni, B: 10 dni

        $podzial = app(PodzialKosztow::class)->dlaBudowy($this->budowa, '2026-09');

        $this->assertSame(225.0, $podzial->firstWhere('contact_id', $this->a->id)['kwota_pln']);
        $this->assertSame(75.0, $podzial->firstWhere('contact_id', $this->b->id)['kwota_pln']);
        $this->assertSame(300.0, round($podzial->sum('kwota_pln'), 2));
    }

    public function test_wskazane_osoby_po_rowno_koszt_osoby_w_calosci_a_reszta_nieprzypisana(): void
    {
        $auto = $this->koszt(['typ' => 'Wynajem samochodu', 'kwota' => 100.01, 'kwota_pln' => 100.01]);
        $auto->osoby()->createMany([['contact_id' => $this->a->id], ['contact_id' => $this->b->id]]);
        $this->koszt(['typ' => 'Bilet lotniczy', 'contact_id' => $this->a->id, 'kwota' => 500, 'kwota_pln' => 500, 'dzielony' => false]);
        $this->koszt(['typ' => 'Bilet lotniczy', 'kwota' => 80, 'kwota_pln' => 80, 'dzielony' => false]);

        $podzial = app(PodzialKosztow::class)->dlaBudowy($this->budowa, '2026-09');

        // 100,01 po równo: 50,01 + 50,00 — grosz idzie do pierwszego, suma się zgadza.
        $this->assertSame(550.01, $podzial->firstWhere('contact_id', $this->a->id)['kwota_pln']);
        $this->assertSame(50.0, $podzial->firstWhere('contact_id', $this->b->id)['kwota_pln']);
        $this->assertSame(80.0, $podzial->firstWhere('contact_id', null)['kwota_pln']);
        $this->assertSame(680.01, round($podzial->sum('kwota_pln'), 2));
    }

    public function test_pokoj_pilnuje_miejsc_i_jednego_lozka_na_osobe(): void
    {
        $pokoj1 = $this->koszt(['typ' => 'Kwatera / pokój', 'od' => '2026-09-01', 'do' => '2026-09-30', 'miejsc' => 1, 'dzielony' => true]);
        $pokoj2 = $this->koszt(['typ' => 'Kwatera / pokój', 'od' => '2026-09-01', 'do' => '2026-09-30', 'miejsc' => 2, 'dzielony' => true]);

        $this->actingAs($this->biuro)->put('/koszty/'.$pokoj1->id.'/osoby', ['osoby' => [
            ['contact_id' => $this->a->id, 'od' => '2026-09-01', 'do' => '2026-09-30'],
            ['contact_id' => $this->b->id, 'od' => '2026-09-10', 'do' => '2026-09-12'],
        ]])->assertSessionHasErrors('osoby');
        $this->assertSame(0, $pokoj1->osoby()->count());

        $this->actingAs($this->biuro)->put('/koszty/'.$pokoj1->id.'/osoby', ['osoby' => [
            ['contact_id' => $this->a->id, 'od' => '2026-09-01', 'do' => '2026-09-30'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(1, $pokoj1->osoby()->count());

        // A śpi już w pokoju 1 — do pokoju 2 w tym terminie nie wejdzie.
        $this->actingAs($this->biuro)->put('/koszty/'.$pokoj2->id.'/osoby', ['osoby' => [
            ['contact_id' => $this->a->id, 'od' => '2026-09-05', 'do' => '2026-09-06'],
        ]])->assertSessionHasErrors('osoby');
    }

    public function test_kierownik_projektu_widzi_tylko_swoje_budowy_a_kierownik_budowy_nic(): void
    {
        $this->koszt(['kwota_pln' => 100]);
        $obcy = $this->koszt(['organization_id' => $this->inna->id, 'plik_sciezka' => 'koszty/x/a.pdf', 'plik_nazwa' => 'a.pdf']);

        $this->actingAs($this->kp)->get('/budowy/'.$this->budowa->id.'/koszty')->assertOk();
        $this->actingAs($this->kp)->get('/budowy/'.$this->inna->id.'/koszty')->assertForbidden();
        $this->actingAs($this->kp)->get('/koszty/'.$obcy->id.'/plik')->assertForbidden();
        $this->actingAs($this->kp)->post('/budowy/'.$this->budowa->id.'/koszty', [])->assertForbidden();

        $this->actingAs($this->kb)->get('/budowy/'.$this->budowa->id.'/koszty')->assertForbidden();
        $this->actingAs($this->biuro)->get('/budowy/'.$this->inna->id.'/koszty')->assertOk();
    }

    public function test_zakladka_pracownika_pokazuje_wlasne_koszty_i_udzialy_z_budow(): void
    {
        $this->koszt(['typ' => 'Bilet lotniczy', 'contact_id' => $this->a->id, 'kwota' => 500, 'kwota_pln' => 500, 'dzielony' => false]);
        $pokoj = $this->koszt(['typ' => 'Kwatera / pokój', 'kwota' => 3000, 'kwota_pln' => 3000, 'od' => '2026-09-01', 'do' => '2026-09-30', 'miejsc' => 3, 'dzielony' => true]);
        $pokoj->osoby()->createMany([
            ['contact_id' => $this->a->id, 'od' => '2026-09-01', 'do' => '2026-09-30'],
            ['contact_id' => $this->b->id, 'od' => '2026-09-16', 'do' => '2026-09-30'],
        ]);

        $props = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->a->id.'/koszty?miesiac=2026-09')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertCount(1, $props['koszty']);
        $this->assertSame(500.0, $props['sumy']['pln']);
        $this->assertSame(2000.0, $props['suma_udzialow']);
        $this->assertSame('Lausitz', $props['udzialy'][0]['budowa']);
        // W karcie osoby nie zakłada się pokoju.
        $this->assertFalse(collect($props['typy'])->contains('nocleg', true));
    }

    public function test_typ_uzyty_na_kosztach_nie_da_sie_usunac(): void
    {
        $this->koszt(['typ' => 'Bilet lotniczy', 'contact_id' => $this->a->id]);
        $uzyty = $this->typ('Bilet lotniczy');
        $wolny = $this->typ('Wynajem samochodu');

        $this->actingAs($this->biuro)->delete('/typy-kosztow/'.$uzyty->id)->assertRedirect();
        $this->assertNull($uzyty->fresh()->deleted_at);

        $this->actingAs($this->biuro)->delete('/typy-kosztow/'.$wolny->id)->assertRedirect();
        $this->assertNotNull($wolny->fresh()->deleted_at);
    }
}
