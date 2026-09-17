<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZgloszenieKierownika;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Zgłoszenia od kierowników do kadr: kierownik zgłasza zjazd/urlop/przeniesienie
 * o swoim pracowniku (niczego sam nie zmienia), kadry obsługują na ekranie Kadry.
 */
class ZgloszeniaKierownikowTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $kierownik;
    private User $kadry;
    private Organization $moja;
    private Organization $cudza;
    private Contact $pracownik;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        Funkcja::create(['id' => Funkcja::KIEROWNIK, 'name' => 'Kierownik Budowy', 'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK]);

        $this->kierownik = $this->user(3, 'kb@mkl.pl');
        $this->kadry = $this->user(6, 'kadry@mkl.pl');
        $this->moja = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Moja']);
        $this->cudza = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Cudza']);

        $szef = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Szef', 'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $this->kierownik->id]);
        ContactWorkDate::create(['contact_id' => $szef->id, 'organization_id' => $this->moja->id, 'start' => now()->subMonth()->toDateString(), 'end' => null]);

        $this->pracownik = $this->osobaNa('Kowalski', $this->moja);
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => $email,
            'owner' => $owner, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function osobaNa(string $nazwisko, Organization $budowa): Contact
    {
        $c = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => $nazwisko]);
        ContactWorkDate::create(['contact_id' => $c->id, 'organization_id' => $budowa->id, 'start' => now()->subWeek()->toDateString(), 'end' => now()->addMonth()->toDateString()]);

        return $c;
    }

    public function test_kierownik_zglasza_zjazd_swojego_pracownika_i_kadry_dostaja_dzwonek(): void
    {
        Notification::fake();

        $this->actingAs($this->kierownik)
            ->from('/pracownicy/'.$this->moja->id)
            ->post('/budowy/'.$this->moja->id.'/zgloszenia', [
                'contact_id' => $this->pracownik->id,
                'rodzaj' => 'urlop',
                'od' => '2026-09-21',
                'do' => '2026-09-25',
                'uwaga' => 'Wesele brata',
                'plik' => UploadedFile::fake()->create('wniosek.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect('/pracownicy/'.$this->moja->id)
            ->assertSessionHas('success');

        $z = ZgloszenieKierownika::sole();
        $this->assertSame('urlop', $z->rodzaj);
        $this->assertSame('nowe', $z->status);
        $this->assertSame($this->kierownik->id, (int) $z->user_id);
        $this->assertSame('wniosek.pdf', $z->plik_nazwa);
        Storage::disk('local')->assertExists($z->plik_sciezka);

        // Dzwonek do tych, którzy obsługują Kadry — nie do samego kierownika.
        Notification::assertSentTo($this->kadry, \App\Notifications\ZgloszenieKierownikaNotification::class);
        Notification::assertNotSentTo($this->kierownik, \App\Notifications\ZgloszenieKierownikaNotification::class);

        // Zgłoszenie samo niczego nie zmienia.
        $this->assertSame(now()->addMonth()->toDateString(), ContactWorkDate::where('contact_id', $this->pracownik->id)->value('end'));
    }

    public function test_kierownik_nie_zglosi_o_cudzym_pracowniku_ani_na_cudzej_budowie(): void
    {
        $obcy = $this->osobaNa('Obcy', $this->cudza);

        $this->actingAs($this->kierownik)
            ->post('/budowy/'.$this->moja->id.'/zgloszenia', ['contact_id' => $obcy->id, 'rodzaj' => 'zjazd'])
            ->assertForbidden();

        $this->actingAs($this->kierownik)
            ->post('/budowy/'.$this->cudza->id.'/zgloszenia', ['contact_id' => $obcy->id, 'rodzaj' => 'zjazd'])
            ->assertForbidden();

        $this->assertSame(0, ZgloszenieKierownika::count());
    }

    public function test_daty_i_rodzaj_sa_sprawdzane(): void
    {
        $this->actingAs($this->kierownik)
            ->from('/pracownicy/'.$this->moja->id)
            ->post('/budowy/'.$this->moja->id.'/zgloszenia', [
                'contact_id' => $this->pracownik->id, 'rodzaj' => 'wakacje', 'od' => '2026-09-25', 'do' => '2026-09-21',
            ])
            ->assertSessionHasErrors(['rodzaj', 'do']);
    }

    public function test_kierownik_widzi_status_swojego_zgloszenia_w_zakladce_pracownicy(): void
    {
        $this->actingAs($this->kierownik)
            ->post('/budowy/'.$this->moja->id.'/zgloszenia', ['contact_id' => $this->pracownik->id, 'rodzaj' => 'zjazd', 'od' => '2026-09-30']);

        $props = $this->actingAs($this->kierownik)->get('/pracownicy/'.$this->moja->id)->viewData('page')['props'];

        $z = $props['zgloszenia'][$this->pracownik->id];
        $this->assertSame('Zjazd z budowy', $z['rodzaj_label']);
        $this->assertSame('czeka na kadry', $z['status_label']);
        $this->assertSame('2026-09-30', $z['od']);
    }

    public function test_kadry_widza_zgloszenie_obsluguja_je_a_kierownik_nie_moze(): void
    {
        $this->actingAs($this->kierownik)
            ->post('/budowy/'.$this->moja->id.'/zgloszenia', ['contact_id' => $this->pracownik->id, 'rodzaj' => 'przeniesienie', 'uwaga' => 'Na Cudzą od października']);
        $z = ZgloszenieKierownika::sole();

        $props = $this->actingAs($this->kadry)->get('/zmiany-kadrowe')->viewData('page')['props'];
        $this->assertSame(1, $props['zgloszenia_licznik']);
        $this->assertSame('Kowalski Adam', $props['zgloszenia'][0]['pracownik']);
        $this->assertSame('Moja', $props['zgloszenia'][0]['budowa']);
        $this->assertNotNull($props['zgloszenia'][0]['pobyt_id'], 'Skrót "Popraw daty pobytu" potrzebuje id bieżącego pobytu.');

        $this->actingAs($this->kierownik)->put('/zgloszenia/'.$z->id, ['status' => 'obsluzone'])->assertForbidden();

        $this->actingAs($this->kadry)
            ->put('/zgloszenia/'.$z->id, ['status' => 'odrzucone', 'odpowiedz' => 'Zostaje do końca miesiąca'])
            ->assertRedirect();

        $z->refresh();
        $this->assertSame('odrzucone', $z->status);
        $this->assertSame($this->kadry->id, (int) $z->obsluzyl_id);
        $this->assertSame('Zostaje do końca miesiąca', $z->odpowiedz);

        // Odrzucone znika z domyślnego widoku, wraca przy "wszystkie".
        $this->assertCount(0, $this->actingAs($this->kadry)->get('/zmiany-kadrowe')->viewData('page')['props']['zgloszenia']);
        $this->assertCount(1, $this->actingAs($this->kadry)->get('/zmiany-kadrowe?pokaz=wszystkie')->viewData('page')['props']['zgloszenia']);

        // Kierownik widzi odpowiedź u siebie.
        $u = $this->actingAs($this->kierownik)->get('/pracownicy/'.$this->moja->id)->viewData('page')['props']['zgloszenia'][$this->pracownik->id];
        $this->assertSame('odrzucone', $u['status']);
        $this->assertSame('Zostaje do końca miesiąca', $u['odpowiedz']);
    }

    public function test_skan_widzi_zglaszajacy_i_kadry_nikt_inny(): void
    {
        $this->actingAs($this->kierownik)
            ->post('/budowy/'.$this->moja->id.'/zgloszenia', [
                'contact_id' => $this->pracownik->id, 'rodzaj' => 'urlop',
                'plik' => UploadedFile::fake()->image('wniosek.jpg'),
            ]);
        $z = ZgloszenieKierownika::sole();

        $this->actingAs($this->kierownik)->get('/zgloszenia/'.$z->id.'/plik')->assertOk();
        $this->actingAs($this->kadry)->get('/zgloszenia/'.$z->id.'/plik')->assertOk();
        $this->actingAs($this->user(3, 'inny@mkl.pl'))->get('/zgloszenia/'.$z->id.'/plik')->assertForbidden();
    }
}
