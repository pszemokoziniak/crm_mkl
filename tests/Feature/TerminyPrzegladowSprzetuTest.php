<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Narzedzia;
use App\Models\Organization;
use App\Models\ToolWorkDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Raport "Termin uprawnień" pilnuje też przeglądów sprzętu (ważność badań).
 * Biuro widzi każdą sztukę, także w magazynie; kierownik tylko to, co stoi
 * dziś na jego budowie.
 */
class TerminyPrzegladowSprzetuTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
        Funkcja::create([
            'id' => Funkcja::KIEROWNIK, 'name' => 'Kierownik Budowy',
            'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
        ]);
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => $email,
            'owner' => $owner, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function sztuka(string $nazwa, ?string $przeglad, ?Organization $budowa = null): Narzedzia
    {
        $n = Narzedzia::create([
            'name' => $nazwa, 'numer_seryjny' => 'SN-'.$nazwa, 'waznosc_badan' => $przeglad,
            'ilosc_all' => 1, 'ilosc_budowa' => $budowa ? 1 : 0, 'ilosc_magazyn' => $budowa ? 0 : 1,
        ]);
        if ($budowa) {
            ToolWorkDate::create([
                'narzedzia_id' => $n->id, 'organization_id' => $budowa->id, 'narzedzia_nb' => 1,
                'start' => now()->subMonth()->toDateString(), 'end' => null,
            ]);
        }

        return $n;
    }

    /** @return array<string, array<string, mixed>> wiersze kategorii Sprzęt po nazwie */
    private function sprzetWRaporcie(User $kto, string $okno = '90'): array
    {
        $wiersze = [];
        $this->actingAs($kto)->get('/reports/koniecUprawinien?days='.$okno)
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$wiersze) {
                $page->component('Reports/TerminUprawnien');
                foreach ($page->toArray()['props']['data'] as $w) {
                    if ($w['category'] === 'Sprzęt') {
                        $wiersze[$w['last_name']] = $w;
                    }
                }
            });

        return $wiersze;
    }

    public function test_biuro_widzi_przeglady_w_oknie_takze_z_magazynu(): void
    {
        $budowa = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Zeitz']);
        $this->sztuka('Zwyżka', now()->addDays(20)->toDateString(), $budowa);
        $this->sztuka('Agregat', now()->addDays(40)->toDateString());          // magazyn
        $this->sztuka('Spawarka', now()->addDays(400)->toDateString());        // poza oknem
        $this->sztuka('Młot', '1970-01-01');                                   // zaślepka z importu
        $this->sztuka('Wciągarka', null);

        $sprzet = $this->sprzetWRaporcie($this->user(2, 'biuro@mkl.pl'));

        $this->assertEqualsCanonicalizing(['Zwyżka', 'Agregat'], array_keys($sprzet));
        $this->assertSame('Przegląd — Zeitz', $sprzet['Zwyżka']['name']);
        $this->assertSame('Przegląd — magazyn', $sprzet['Agregat']['name']);
        $this->assertSame('SN-Zwyżka', $sprzet['Zwyżka']['first_name']);
        $this->assertSame(now()->addDays(20)->toDateString(), $sprzet['Zwyżka']['end']);
        $this->assertStringEndsWith('/narzedzia/'.Narzedzia::where('name', 'Zwyżka')->value('id').'/edit', $sprzet['Zwyżka']['url']);
    }

    public function test_kierownik_widzi_tylko_sprzet_stojacy_dzis_na_jego_budowie(): void
    {
        $moja = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Moja']);
        $cudza = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Cudza']);

        $kierownik = $this->user(3, 'kb@mkl.pl');
        $szef = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Szef',
            'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $kierownik->id,
        ]);
        ContactWorkDate::create([
            'contact_id' => $szef->id, 'organization_id' => $moja->id,
            'start' => now()->subMonth()->toDateString(), 'end' => null,
        ]);

        $this->sztuka('Zwyżka', now()->addDays(10)->toDateString(), $moja);
        $this->sztuka('Koparka', now()->addDays(10)->toDateString(), $cudza);
        $this->sztuka('Agregat', now()->addDays(10)->toDateString());
        $zwrocona = $this->sztuka('Wiertnica', now()->addDays(10)->toDateString(), $moja);
        ToolWorkDate::where('narzedzia_id', $zwrocona->id)->update(['end' => now()->subDay()->toDateString()]);

        $sprzet = $this->sprzetWRaporcie($kierownik);

        $this->assertSame(['Zwyżka'], array_keys($sprzet));
        // Kierownik nie ma wstępu do magazynu — link prowadzi do sprzętu jego budowy.
        $this->assertSame('/budowy/'.$moja->id.'/narzedzia', $sprzet['Zwyżka']['url']);
    }
}
