<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Polski alfabet w sortowaniu: wszystkie nazwiska na S idą przed tymi na Ś.
 * Kolacja siedzi na kolumnach, więc dotyczy każdego miejsca, które sortuje
 * po nazwisku — także tych dopisanych w przyszłości.
 */
class SortowaniePolskieTest extends TestCase
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

        // Listy wyboru łączą się ze stanowiskami, więc bez funkcji
        // pracownik w ogóle się na nich nie pojawia.
        $monter = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);

        // Kolejność ze zgłoszenia: Ś wpadało między S.
        foreach (['Skoczylas', 'Śledź', 'Sobiczewski', 'Sujka', 'Świetlicki', 'Szafrański'] as $nazwisko) {
            Contact::create([
                'account_id' => $this->accountId,
                'first_name' => 'Jan',
                'last_name' => $nazwisko,
                'funkcja_id' => $monter->id,
                'status_zatrudnienia' => Contact::STATUS_AKTYWNY,
            ]);
        }
    }

    /** Wszystkie S przed wszystkimi Ś. */
    private const OCZEKIWANA = [
        'Skoczylas', 'Sobiczewski', 'Sujka', 'Szafrański', 'Śledź', 'Świetlicki',
    ];

    public function test_baza_sortuje_polskim_alfabetem(): void
    {
        $this->assertSame(
            self::OCZEKIWANA,
            Contact::orderBy('last_name')->pluck('last_name')->all()
        );
    }

    public function test_lista_pracownikow(): void
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/contacts');

        $this->assertSame(
            self::OCZEKIWANA,
            collect($odpowiedz->viewData('page')['props']['contacts']['data'])->pluck('last_name')->all()
        );
    }

    public function test_lista_wolnych_przy_przypisywaniu_do_budowy(): void
    {
        // To miejsce ze zgłoszenia: wybór pracowników na budowę.
        $odpowiedz = $this->actingAs($this->biuro)
            ->post('/pracownicy/'.$this->budowa->id.'/create', [
                'start' => '2026-09-01',
                'end' => '2026-12-31',
            ]);

        $odpowiedz->assertOk();

        $nazwiska = collect($odpowiedz->viewData('page')['props']['contactsFree'])->pluck('last_name')->all();

        $this->assertSame(self::OCZEKIWANA, $nazwiska);
    }

    public function test_lista_kierownictwa(): void
    {
        $funkcja = Funkcja::create(['name' => 'Kierownik Budowy', 'kierownictwo' => true]);
        Contact::query()->update(['funkcja_id' => $funkcja->id]);

        $odpowiedz = $this->actingAs($this->biuro)->get('/budowy/'.$this->budowa->id.'/kierownictwo');

        $this->assertSame(
            self::OCZEKIWANA,
            collect($odpowiedz->viewData('page')['props']['specialists'])->pluck('last_name')->all()
        );
    }

    public function test_nazwy_budow_tez(): void
    {
        Organization::query()->delete();
        foreach (['Swarzędz', 'Świdno', 'Szczecin'] as $nazwa) {
            Organization::create(['account_id' => 0, 'name' => 'Klient', 'nazwaBud' => $nazwa]);
        }

        $this->assertSame(
            ['Swarzędz', 'Szczecin', 'Świdno'],
            Organization::orderBy('nazwaBud')->pluck('nazwaBud')->all()
        );
    }
}
