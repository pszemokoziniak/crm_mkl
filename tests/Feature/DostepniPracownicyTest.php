<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtr "Dostępni" na liście pracowników ma odpowiadać na pytanie
 * "kogo mogę wysłać na budowę". Zwolniony nie jest dostępny, choćby
 * miał wolny termin — przy przypisywaniu i tak go nie ma na liście.
 */
class DostepniPracownicyTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Organization $budowa;

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

        $this->budowa = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '504_Valmet']);
    }

    private function pracownik(string $nazwisko, string $status = Contact::STATUS_AKTYWNY): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
            'status_zatrudnienia' => $status,
        ]);
    }

    private function dostepni(): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/contacts?status=dostepni');
        $odpowiedz->assertOk();

        return collect($odpowiedz->viewData('page')['props']['contacts']['data'])->pluck('last_name')->all();
    }

    public function test_zwolniony_nie_jest_dostepny(): void
    {
        $this->pracownik('Aktywny');
        $this->pracownik('Zwolniony', Contact::STATUS_ZWOLNIONY);

        $this->assertSame(['Aktywny'], $this->dostepni());
    }

    public function test_na_urlopie_zostaje_dostepny(): void
    {
        // Urlop to nie rozstanie z firmą — taka osoba wraca i można ją planować.
        $this->pracownik('Urlopowicz', Contact::STATUS_URLOP);

        $this->assertSame(['Urlopowicz'], $this->dostepni());
    }

    public function test_pracujacy_na_budowie_nie_jest_dostepny(): void
    {
        $naBudowie = $this->pracownik('Zajety');
        ContactWorkDate::create([
            'contact_id' => $naBudowie->id,
            'organization_id' => $this->budowa->id,
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);
        $this->pracownik('Wolny');

        $this->assertSame(['Wolny'], $this->dostepni());
    }

    public function test_filtr_na_budowie_dziala_jak_dotad(): void
    {
        $naBudowie = $this->pracownik('Zajety');
        ContactWorkDate::create([
            'contact_id' => $naBudowie->id,
            'organization_id' => $this->budowa->id,
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);
        $this->pracownik('Wolny');

        $odpowiedz = $this->actingAs($this->biuro)->get('/contacts?status=na_budowie');
        $nazwiska = collect($odpowiedz->viewData('page')['props']['contacts']['data'])->pluck('last_name');

        $this->assertSame(['Zajety'], $nazwiska->all());
    }

    public function test_lista_bez_filtra_pokazuje_wszystkich(): void
    {
        $this->pracownik('Aktywny');
        $this->pracownik('Zwolniony', Contact::STATUS_ZWOLNIONY);

        $odpowiedz = $this->actingAs($this->biuro)->get('/contacts');
        $nazwiska = collect($odpowiedz->viewData('page')['props']['contacts']['data'])->pluck('last_name');

        $this->assertCount(2, $nazwiska);
    }

    public function test_przywrocony_z_archiwum_wraca_na_liste_dostepnych(): void
    {
        // Pełna droga ze zgłoszenia: zwolnienie, archiwum, przywrócenie.
        $pracownik = $this->pracownik('Barczuk', Contact::STATUS_ZWOLNIONY);
        $pracownik->delete();

        $this->actingAs($this->biuro)->put('/contacts/'.$pracownik->id.'/restore');

        $this->assertSame(Contact::STATUS_AKTYWNY, $pracownik->fresh()->status_zatrudnienia);
        $this->assertSame(['Barczuk'], $this->dostepni());
    }
}
