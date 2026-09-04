<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Zakładka "Kierownicy i inżynierowie" — ta sama lista co Pracownicy,
 * tylko inny zbiór osób. O przynależności decyduje znacznik przy
 * stanowisku w Ustawieniach, więc nowe stanowisko można przypisać
 * do jednej albo drugiej zakładki bez zmiany w kodzie.
 */
class ZakladkaKierownicyTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private int $accountId;
    private Funkcja $kierownikBudowy;
    private Funkcja $monter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->kierownikBudowy = Funkcja::create(['name' => 'Kierownik Budowy', 'kierownictwo' => true]);
        $this->monter = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);
    }

    private function pracownik(string $nazwisko, ?Funkcja $funkcja): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
            'funkcja_id' => optional($funkcja)->id,
        ]);
    }

    private function nazwiska($odpowiedz, string $adres): array
    {
        $dane = $odpowiedz->viewData('page')['props']['contacts']['data'];

        return collect($dane)->pluck('last_name')->all();
    }

    public function test_kierownictwo_widac_tylko_w_nowej_zakladce(): void
    {
        $this->pracownik('Kierowniczak', $this->kierownikBudowy);
        $this->pracownik('Monterski', $this->monter);

        $kierownicy = $this->actingAs($this->biuro)->get('/kierownicy');
        $kierownicy->assertOk();
        $this->assertSame(['Kierowniczak'], $this->nazwiska($kierownicy, '/kierownicy'));

        $pracownicy = $this->actingAs($this->biuro)->get('/contacts');
        $pracownicy->assertOk();
        $this->assertSame(['Monterski'], $this->nazwiska($pracownicy, '/contacts'));
    }

    public function test_pracownik_bez_stanowiska_zostaje_w_pracownikach(): void
    {
        $this->pracownik('Bezstanowiskowy', null);

        $this->assertSame(
            ['Bezstanowiskowy'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/contacts'), '/contacts')
        );

        $this->assertSame(
            [],
            $this->nazwiska($this->actingAs($this->biuro)->get('/kierownicy'), '/kierownicy')
        );
    }

    public function test_zmiana_znacznika_przenosi_stanowisko_miedzy_zakladkami(): void
    {
        $this->pracownik('Spawalski', $this->monter);

        // Zanim biuro oznaczy stanowisko — osoba jest w Pracownikach.
        $this->assertSame(
            ['Spawalski'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/contacts'), '/contacts')
        );

        $this->monter->update(['kierownictwo' => true]);

        // Po oznaczeniu — bez żadnej zmiany w kodzie — jest w Kierownictwie.
        $this->assertSame(
            ['Spawalski'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/kierownicy'), '/kierownicy')
        );
        $this->assertSame(
            [],
            $this->nazwiska($this->actingAs($this->biuro)->get('/contacts'), '/contacts')
        );
    }

    public function test_nowa_zakladka_ma_te_same_kolumny_i_naglowek(): void
    {
        $this->pracownik('Kierowniczak', $this->kierownikBudowy);

        $this->actingAs($this->biuro)
            ->get('/kierownicy')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contacts/Index')
                ->where('naglowek', 'Kierownicy i inżynierowie')
                ->where('adresListy', '/kierownicy')
                ->has('contacts.data.0', fn (Assert $wiersz) => $wiersz
                    ->where('last_name', 'Kierowniczak')
                    ->has('funkcja')
                    ->has('pracuje')
                    ->etc()
                )
            );
    }

    public function test_filtr_szukania_dziala_w_nowej_zakladce(): void
    {
        $this->pracownik('Kierowniczak', $this->kierownikBudowy);
        $this->pracownik('Nowacki', $this->kierownikBudowy);

        $this->assertSame(
            ['Nowacki'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/kierownicy?search=Nowacki'), '/kierownicy')
        );
    }

    public function test_filtr_na_budowie_dziala_w_nowej_zakladce(): void
    {
        $naBudowie = $this->pracownik('Kierowniczak', $this->kierownikBudowy);
        $this->pracownik('Wolny', $this->kierownikBudowy);

        $budowa = Organization::create([
            'account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '434_Valmet',
        ]);
        ContactWorkDate::create([
            'contact_id' => $naBudowie->id,
            'organization_id' => $budowa->id,
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);

        $this->assertSame(
            ['Kierowniczak'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/kierownicy?status=na_budowie'), '/kierownicy')
        );
        $this->assertSame(
            ['Wolny'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/kierownicy?status=dostepni'), '/kierownicy')
        );
    }

    public function test_filtr_archiwum_dziala_w_nowej_zakladce(): void
    {
        $this->pracownik('Kierowniczak', $this->kierownikBudowy)->delete();
        $this->pracownik('Aktywny', $this->kierownikBudowy);

        $this->assertSame(
            ['Aktywny'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/kierownicy'), '/kierownicy')
        );
        $this->assertSame(
            ['Kierowniczak'],
            $this->nazwiska($this->actingAs($this->biuro)->get('/kierownicy?trashed=only'), '/kierownicy')
        );
    }

    public function test_kierownik_nie_wchodzi_do_zakladki(): void
    {
        $kierownik = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'kierownik@mkl.pl',
            'owner' => 3,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($kierownik)->get('/kierownicy')->assertStatus(403);
    }

    public function test_nikt_nie_wypada_z_obu_list(): void
    {
        $this->pracownik('Kierowniczak', $this->kierownikBudowy);
        $this->pracownik('Monterski', $this->monter);
        $this->pracownik('Bezstanowiskowy', null);

        $razem = Contact::kierownictwo(true)->count() + Contact::kierownictwo(false)->count();

        $this->assertSame(Contact::count(), $razem);
    }
}
