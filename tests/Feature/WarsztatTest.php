<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\KrajTyp;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Warsztaty (Łuków, Siedlce) prowadzimy jak budowy — inaczej nie da się
 * przypisać pracownika ani rozliczyć mu godzin. Znacznik "warsztat"
 * odróżnia je tam, gdzie liczymy budowy i planujemy obsadę.
 */
class WarsztatTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private int $accountId;
    private int $krajId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->krajId = KrajTyp::create(['name' => 'Polska'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function budowa(string $nazwa, bool $warsztat = false): Organization
    {
        return Organization::create([
            'account_id' => 0,
            'name' => $warsztat ? 'MKL BAU' : 'Valmet',
            'nazwaBud' => $nazwa,
            'country_id' => $this->krajId,
            'warsztat' => $warsztat,
        ]);
    }

    public function test_zakladanie_warsztatu_zapisuje_znacznik(): void
    {
        $this->actingAs($this->biuro)
            ->post('/budowy', [
                'name' => 'MKL BAU',
                'nazwaBud' => 'Warsztat Łuków',
                'country_id' => $this->krajId,
                'warsztat' => true,
            ])
            ->assertRedirect();

        $this->assertTrue(Organization::firstWhere('nazwaBud', 'Warsztat Łuków')->warsztat);
    }

    public function test_zwykla_budowa_nie_jest_warsztatem(): void
    {
        $this->actingAs($this->biuro)
            ->post('/budowy', [
                'name' => 'Valmet',
                'nazwaBud' => '500_Valmet',
                'country_id' => $this->krajId,
            ])
            ->assertRedirect();

        $this->assertFalse(Organization::firstWhere('nazwaBud', '500_Valmet')->warsztat);
    }

    public function test_warsztat_jest_na_liscie_budow_z_plakietka(): void
    {
        $this->budowa('Warsztat Łuków', true);

        $this->actingAs($this->biuro)
            ->get('/budowy?search=Warsztat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organizations.data.0.nazwaBud', 'Warsztat Łuków')
                ->where('organizations.data.0.warsztat', true)
            );
    }

    public function test_warsztatu_nie_ma_w_prognozie(): void
    {
        $this->budowa('434_Valmet');
        $this->budowa('Warsztat Łuków', true);

        $odpowiedz = $this->actingAs($this->biuro)->get('/prognoza');
        $odpowiedz->assertOk();

        $nazwy = collect($odpowiedz->viewData('page')['props']['buildings'])->pluck('nazwaBud');

        $this->assertContains('434_Valmet', $nazwy);
        $this->assertNotContains('Warsztat Łuków', $nazwy);
    }

    public function test_warsztat_nie_liczy_sie_do_liczby_budow(): void
    {
        $this->budowa('434_Valmet');
        $this->budowa('Warsztat Łuków', true);
        $this->budowa('Warsztat Siedlce', true);

        $odpowiedz = $this->actingAs($this->biuro)->get('/');
        $odpowiedz->assertOk();

        $this->assertSame(1, $odpowiedz->viewData('page')['props']['stats']['budowy']);
    }

    public function test_pracownika_da_sie_przypisac_do_warsztatu(): void
    {
        $warsztat = $this->budowa('Warsztat Łuków', true);
        $pracownik = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);

        $this->actingAs($this->biuro)
            ->post('/contacts/'.$pracownik->id.'/przypisz-budowe', [
                'organization_id' => $warsztat->id,
                'start' => now()->toDateString(),
                'end' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contact_work_dates', [
            'contact_id' => $pracownik->id,
            'organization_id' => $warsztat->id,
        ]);
    }

    public function test_kolumna_obecnie_pokazuje_warsztat(): void
    {
        $warsztat = $this->budowa('Warsztat Łuków', true);
        $pracownik = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);
        ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $warsztat->id,
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($this->biuro)
            ->get('/contacts')
            ->assertInertia(fn (Assert $page) => $page
                ->where('contacts.data.0.pracuje.typ', 'budowa')
                ->where('contacts.data.0.pracuje.label', 'Warsztat Łuków')
                ->etc()
            );
    }
}
