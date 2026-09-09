<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\ShiftStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kierownik budowy ma własny pulpit — dotąd był z niego wyrzucany na listę
 * budów. Widzi na nim tylko swoje budowy i swoich ludzi; sprawy biura
 * (archiwizacja, sprzęt, cała kadra) go nie dotyczą.
 */
class PulpitKierownikaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $kierownik;
    private Contact $kierownikContact;
    private Organization $mojaBudowa;
    private Organization $cudzaBudowa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        // O tym, czy stanowisko wchodzi w kierownictwo budowy, decyduje
        // przypisanie do kolumny w słowniku — bez niego kierownik nie miałby
        // żadnej budowy (Organization::scopeManagedBy).
        $funkcjaKierownik = Funkcja::create([
            'id' => Funkcja::KIEROWNIK,
            'name' => 'Kierownik Budowy',
            'kierownictwo' => true,
            'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
        ]);

        $this->kierownik = User::factory()->create([
            'account_id' => $this->accountId,
            'first_name' => 'Adam',
            'last_name' => 'Kierowniczak',
            'email' => 'kierownik@mkl.pl',
            'owner' => 3,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->kierownikContact = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Adam',
            'last_name' => 'Kierowniczak',
            'funkcja_id' => $funkcjaKierownik->id,
            'user_id' => $this->kierownik->id,
        ]);

        $this->mojaBudowa = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => 'Moja budowa']);
        $this->cudzaBudowa = Organization::create(['account_id' => 0, 'name' => 'Andritz', 'nazwaBud' => 'Cudza budowa']);

        // Kierownictwo na własnej budowie.
        ContactWorkDate::create([
            'contact_id' => $this->kierownikContact->id,
            'organization_id' => $this->mojaBudowa->id,
            'start' => now()->subMonth()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);
    }

    private function pracownikNaBudowie(string $nazwisko, Organization $budowa): Contact
    {
        $c = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
        ]);

        ContactWorkDate::create([
            'contact_id' => $c->id,
            'organization_id' => $budowa->id,
            'start' => now()->subWeek()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);

        return $c;
    }

    public function test_kierownik_wchodzi_na_pulpit_zamiast_byc_przekierowanym(): void
    {
        $this->actingAs($this->kierownik)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Index')->etc());
    }

    public function test_liczniki_pokazuja_tylko_jego_budowy_i_ludzi(): void
    {
        $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $this->pracownikNaBudowie('Obcy', $this->cudzaBudowa);

        $this->actingAs($this->kierownik)
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page
                // On sam też jest na budowie, więc dwie osoby.
                ->where('stats.pracownicy', 2)
                ->where('stats.budowy', 1)
                ->where('stats.sprzet', null)
                ->etc()
            );
    }

    public function test_nie_dostaje_listy_budow_do_archiwizacji(): void
    {
        $this->actingAs($this->kierownik)
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('do_archiwizacji', [])->etc());
    }

    public function test_lista_bez_a1_obejmuje_tylko_jego_ludzi(): void
    {
        $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $this->pracownikNaBudowie('Obcy', $this->cudzaBudowa);

        $this->actingAs($this->kierownik)
            ->get('/')
            ->assertInertia(function (Assert $page) {
                $page->etc();
                $nazwiska = collect($page->toArray()['props']['bez_a1'])->pluck('last_name');

                $this->assertContains('Mojski', $nazwiska);
                $this->assertNotContains('Obcy', $nazwiska);
            });
    }

    public function test_pulpit_pokazuje_przeterminowane_dokumenty(): void
    {
        $pracownik = $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);

        // Trzy różne szkolenia: po terminie, kończące się wkrótce i odległe.
        // Różne rodzaje, bo wpis zastąpiony nowszym tego samego rodzaju
        // celowo nie trafia już na pulpit (patrz test o zastąpieniu).
        foreach ([
            'Szkolenie okresowe' => now()->subMonths(2)->toDateString(),
            'Szkolenie wstępne' => now()->addDays(10)->toDateString(),
            'Szkolenie dla kierujących' => now()->addYears(2)->toDateString(),
        ] as $nazwa => $koniec) {
            \App\Models\Bhp::create([
                'contact_id' => $pracownik->id,
                'bhpTyp_id' => \App\Models\BhpTyp::create(['name' => $nazwa])->id,
                'start' => now()->subYear()->toDateString(),
                'end' => $koniec,
            ]);
        }

        $props = $this->actingAs($this->kierownik)->get('/')->viewData('page')['props'];
        $terminy = collect($props['expiring_items']);

        // Przeterminowane dotąd w ogóle się nie pokazywały.
        $this->assertSame(1, $terminy->where('status', 'po_terminie')->count());
        $this->assertSame(1, $terminy->where('status', 'wkrotce')->count());
        $this->assertSame(0, $terminy->where('status', 'dalej')->count(), 'Odległy termin nie powinien tu trafiać.');
        $this->assertSame(2, $props['stats']['wygasajace']);
        $this->assertLessThan(0, $terminy->firstWhere('status', 'po_terminie')['dni']);
    }

    public function test_wpis_zastapiony_nowszym_znika_z_terminow(): void
    {
        // Zgłoszenie: pulpit pokazywał "208 dni po terminie", a karta pracownika
        // badania ważne do 2028. Pracownik miał dwa wpisy tego samego rodzaju —
        // pulpit brał oba, karta tylko najnowszy.
        $pracownik = $this->pracownikNaBudowie('Izdebski', $this->mojaBudowa);
        $typ = \App\Models\BadaniaTyp::create(['name' => 'badanie okresowe']);

        \App\Models\Badania::create([
            'contact_id' => $pracownik->id, 'badaniaTyp_id' => $typ->id,
            'start' => now()->subYears(2)->toDateString(),
            'end' => now()->subDays(208)->toDateString(),
        ]);
        \App\Models\Badania::create([
            'contact_id' => $pracownik->id, 'badaniaTyp_id' => $typ->id,
            'start' => now()->subMonths(3)->toDateString(),
            'end' => now()->addYears(2)->toDateString(),
        ]);

        $terminy = collect($this->actingAs($this->kierownik)->get('/')->viewData('page')['props']['expiring_items']);

        $this->assertCount(0, $terminy->where('category', 'Badania lekarskie'),
            'Stare badanie zastąpione nowym nie jest terminem do pilnowania.');
    }

    public function test_rozne_rodzaje_uprawnien_liczą_sie_osobno(): void
    {
        // Zawężamy do najnowszego w obrębie rodzaju, nie kategorii — inaczej
        // nowe uprawnienie zasłoniłoby przeterminowane, zupełnie inne.
        $pracownik = $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $koparka = \App\Models\UprawnieniaTyp::create(['name' => 'Operator koparki']);
        $wysokosc = \App\Models\UprawnieniaTyp::create(['name' => 'Praca na wysokości']);

        \App\Models\Uprawnienia::create([
            'contact_id' => $pracownik->id, 'uprawnieniaTyp_id' => $koparka->id,
            'start' => now()->subYears(2)->toDateString(), 'end' => now()->subMonth()->toDateString(),
        ]);
        \App\Models\Uprawnienia::create([
            'contact_id' => $pracownik->id, 'uprawnieniaTyp_id' => $wysokosc->id,
            'start' => now()->subMonths(2)->toDateString(), 'end' => now()->addYears(2)->toDateString(),
        ]);

        $terminy = collect($this->actingAs($this->kierownik)->get('/')->viewData('page')['props']['expiring_items']);

        $this->assertCount(1, $terminy->where('category', 'Uprawnienia'));
        $this->assertSame('Operator koparki', $terminy->firstWhere('category', 'Uprawnienia')['type']);
    }

    public function test_pulpit_nie_powiela_listy_budow(): void
    {
        // Listy budów zdjęte z pulpitu: to samo jest w zakładce Budowy, razem
        // z filtrowaniem. Asercja pilnuje, żeby ciężkie zapytania tu nie wróciły.
        $biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => 2, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        foreach ([$this->kierownik, $biuro] as $kto) {
            $props = $this->actingAs($kto)->get('/')->viewData('page')['props'];

            $this->assertArrayNotHasKey('organizations_user', $props);
            $this->assertArrayNotHasKey('organizations_biuro', $props);
            $this->assertArrayNotHasKey('filters', $props);
            $this->assertArrayHasKey('stats', $props, 'Reszta pulpitu zostaje.');
        }
    }

    public function test_kierownik_wchodzi_z_terminu_na_karte_pracownika(): void
    {
        // Wiersz "Terminów do pilnowania" prowadzi na kartę pracownika.
        // Dla kierownika był to dotąd zwykły tekst, bez odnośnika.
        $pracownik = $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $typ = \App\Models\BhpTyp::create(['name' => 'Szkolenie okresowe']);
        \App\Models\Bhp::create([
            'contact_id' => $pracownik->id, 'bhpTyp_id' => $typ->id,
            'start' => now()->subYear()->toDateString(), 'end' => now()->addDays(10)->toDateString(),
        ]);

        $props = $this->actingAs($this->kierownik)->get('/')->viewData('page')['props'];
        $wiersz = collect($props['expiring_items'])->firstWhere('status', 'wkrotce');

        $this->assertNotNull($wiersz);
        $this->assertSame($pracownik->id, $wiersz['contact']['id'], 'Bez id nie da się zbudować odnośnika.');

        // Pulpit pokazuje kierownikowi tylko jego ludzi, więc karta musi się otworzyć.
        $this->actingAs($this->kierownik)
            ->get("/contacts/{$wiersz['contact']['id']}/edit")
            ->assertOk();
    }

    public function test_lista_bez_a1_rozroznia_brak_wpisu_od_wygaslego(): void
    {
        $zWygaslym = $this->pracownikNaBudowie('Wygasly', $this->mojaBudowa);
        $this->pracownikNaBudowie('Bezwpisu', $this->mojaBudowa);

        \App\Models\A1::create([
            'contact_id' => $zWygaslym->id,
            'start' => now()->subYears(2)->toDateString(),
            'end' => now()->subMonth()->toDateString(),
        ]);

        $props = $this->actingAs($this->kierownik)->get('/')->viewData('page')['props'];
        $lista = collect($props['bez_a1'])->keyBy('last_name');

        $this->assertSame(now()->subMonth()->toDateString(), $lista['Wygasly']['ostatni_a1']);
        $this->assertNull($lista['Bezwpisu']['ostatni_a1']);
    }

    public function test_pulpit_pokazuje_kto_dzis_jest_nieobecny(): void
    {
        $urlopId = ShiftStatus::create(['title' => 'Urlop wypoczynkowy', 'code' => 'UW'])->id;

        $naUrlopie = $this->pracownikNaBudowie('Urlopowicz', $this->mojaBudowa);
        $this->pracownikNaBudowie('Obecny', $this->mojaBudowa);
        $obcy = $this->pracownikNaBudowie('Obcy', $this->cudzaBudowa);

        foreach ([$naUrlopie, $obcy] as $c) {
            Holiday::create([
                'contact_id' => $c->id,
                'shift_status_id' => $urlopId,
                'start' => now()->subDay()->toDateString(),
                'end' => now()->addDays(3)->toDateString(),
            ]);
        }

        $props = $this->actingAs($this->kierownik)->get('/')->viewData('page')['props'];
        $nieobecni = collect($props['nieobecni_dzis']);

        $this->assertCount(1, $nieobecni, 'Cudza budowa i osoby obecne nie powinny tu trafiać.');
        $this->assertSame('Urlopowicz Jan', $nieobecni->first()['pracownik']);
        $this->assertSame('Urlop wypoczynkowy', $nieobecni->first()['powod']);
        $this->assertSame($naUrlopie->id, $nieobecni->first()['contact_id']);
    }

    public function test_zakonczona_nieobecnosc_nie_wisi_na_pulpicie(): void
    {
        $urlopId = ShiftStatus::create(['title' => 'Urlop wypoczynkowy', 'code' => 'UW'])->id;
        $pracownik = $this->pracownikNaBudowie('Wrocil', $this->mojaBudowa);

        Holiday::create([
            'contact_id' => $pracownik->id,
            'shift_status_id' => $urlopId,
            'start' => now()->subDays(10)->toDateString(),
            'end' => now()->subDays(2)->toDateString(),
        ]);

        $props = $this->actingAs($this->kierownik)->get('/')->viewData('page')['props'];

        $this->assertSame([], (array) $props['nieobecni_dzis']);
    }

    public function test_biuro_nie_dostaje_listy_nieobecnych_na_pulpicie(): void
    {
        $biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro2@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $props = $this->actingAs($biuro)->get('/')->viewData('page')['props'];

        $this->assertSame([], (array) $props['nieobecni_dzis']);
    }

    public function test_kierownik_wchodzi_w_raport_terminow_ale_widzi_tylko_swoich(): void
    {
        $moj = $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $obcy = $this->pracownikNaBudowie('Obcy', $this->cudzaBudowa);

        $typ = \App\Models\BhpTyp::create(['name' => 'Szkolenie okresowe']);

        foreach ([$moj, $obcy] as $c) {
            \App\Models\Bhp::create([
                'contact_id' => $c->id,
                'bhpTyp_id' => $typ->id,
                'start' => now()->subYear()->toDateString(),
                'end' => now()->addDays(10)->toDateString(),
            ]);
        }

        $props = $this->actingAs($this->kierownik)
            ->get('/reports/koniecUprawinien')
            ->assertOk()
            ->viewData('page')['props'];

        $nazwiska = collect($props['data'])->pluck('last_name');
        $this->assertContains('Mojski', $nazwiska);
        $this->assertNotContains('Obcy', $nazwiska);

        $braki = collect($props['braki'])->pluck('name');
        $this->assertTrue($braki->contains(fn ($n) => str_contains($n, 'Mojski')));
        $this->assertFalse($braki->contains(fn ($n) => str_contains($n, 'Obcy')));
    }

    public function test_raport_terminow_domyslnie_pokazuje_90_dni(): void
    {
        // Uprawnienia chodzą w cyklach rocznych, więc przy oknie 30 dni raport
        // wyglądał, jakby dotyczył wyłącznie A1 — kierownik nie widział ich wcale.
        $pracownik = $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $typ = \App\Models\UprawnieniaTyp::create(['name' => 'Praca na wysokości']);
        \App\Models\Uprawnienia::create([
            'contact_id' => $pracownik->id, 'uprawnieniaTyp_id' => $typ->id,
            'start' => now()->subYear()->toDateString(),
            'end' => now()->addDays(60)->toDateString(),
        ]);

        $props = $this->actingAs($this->kierownik)
            ->get('/reports/koniecUprawinien')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame('90', $props['filters']['days'], 'Ekran ma startować z oknem 90 dni.');
        $this->assertContains(
            'Praca na wysokości',
            collect($props['data'])->where('category', 'Uprawnienia')->pluck('name'),
            'Uprawnienie kończące się za 60 dni ma być widoczne bez zmiany filtra.'
        );
    }

    public function test_biuro_dalej_widzi_w_raporcie_wszystkich(): void
    {
        $biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro3@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $obcy = $this->pracownikNaBudowie('Obcy', $this->cudzaBudowa);

        $typ = \App\Models\BhpTyp::create(['name' => 'Szkolenie okresowe']);
        \App\Models\Bhp::create([
            'contact_id' => $obcy->id,
            'bhpTyp_id' => $typ->id,
            'start' => now()->subYear()->toDateString(),
            'end' => now()->addDays(10)->toDateString(),
        ]);

        $props = $this->actingAs($biuro)
            ->get('/reports/koniecUprawinien')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertContains('Obcy', collect($props['data'])->pluck('last_name'));
    }

    public function test_biuro_dalej_widzi_wszystko(): void
    {
        $biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->pracownikNaBudowie('Mojski', $this->mojaBudowa);
        $this->pracownikNaBudowie('Obcy', $this->cudzaBudowa);

        $this->actingAs($biuro)
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.budowy', 2)
                ->where('stats.pracownicy', 3)
                ->etc()
            );
    }
}
