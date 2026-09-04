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

        // Id wpisujemy wprost: Organization::scopeManagedBy filtruje po stałej
        // Funkcja::KIEROWNIK, więc bez tego kierownik nie miałby żadnej budowy.
        $funkcjaKierownik = Funkcja::create([
            'id' => Funkcja::KIEROWNIK,
            'name' => 'Kierownik Budowy',
            'kierownictwo' => true,
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
