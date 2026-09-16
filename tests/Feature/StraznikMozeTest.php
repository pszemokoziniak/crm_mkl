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
use Tests\TestCase;

/**
 * Strażnik `moze:` na żywych żądaniach: uprawnienie decyduje CZY rola
 * wchodzi w obszar, a zakres (własne budowy i ludzie) zostaje osobno
 * i dalej działa dla kierownika.
 */
class StraznikMozeTest extends TestCase
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

    private function kierownikNaBudowie(User $user, Organization $budowa): void
    {
        $szef = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Szef',
            'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $user->id,
        ]);
        ContactWorkDate::create([
            'contact_id' => $szef->id, 'organization_id' => $budowa->id,
            'start' => now()->subMonth()->toDateString(), 'end' => null,
        ]);
    }

    public function test_kierownik_nie_wchodzi_na_liste_pracownikow_ale_na_swoja_budowe_tak(): void
    {
        $moja = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Moja']);
        $cudza = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Cudza']);
        $kierownik = $this->user(3, 'kb@mkl.pl');
        $this->kierownikNaBudowie($kierownik, $moja);

        $this->actingAs($kierownik)->get('/contacts')->assertForbidden();
        $this->actingAs($kierownik)->get('/budowy')->assertOk();
        $this->actingAs($kierownik)->get("/building/{$moja->id}/time-sheet")->assertOk();
        // Uprawnienie jest, zakresu nie ma — zakres dalej zatrzymuje.
        $this->actingAs($kierownik)->get("/building/{$cudza->id}/time-sheet")->assertForbidden();
    }

    public function test_biuro_nie_wchodzi_do_slownikow_systemowych_admin_tak(): void
    {
        $this->actingAs($this->user(2, 'biuro@mkl.pl'))->get('/badaniaTyp')->assertForbidden();
        $this->actingAs($this->user(6, 'kadry@mkl.pl'))->get('/badaniaTyp')->assertForbidden();
        $this->actingAs($this->user(1, 'admin@mkl.pl'))->get('/badaniaTyp')->assertOk();
    }

    public function test_wlasny_profil_edytuje_kazdy_cudzy_tylko_z_uprawnieniem(): void
    {
        $kierownik = $this->user(3, 'kb@mkl.pl');
        $inny = $this->user(3, 'inny@mkl.pl');
        $biuro = $this->user(2, 'biuro@mkl.pl');

        $this->actingAs($kierownik)->get("/users/{$kierownik->id}/edit")->assertOk();
        $this->actingAs($kierownik)->get("/users/{$inny->id}/edit")->assertForbidden();
        $this->actingAs($biuro)->get("/users/{$inny->id}/edit")->assertOk();
    }

    public function test_widoki_dostaja_mape_uprawnien_pod_ta_sama_nazwa_co_trasy(): void
    {
        $moze = $this->user(3, 'kb@mkl.pl')->permissions['moze'];

        $this->assertTrue($moze['budowy.podglad']);
        $this->assertFalse($moze['kartoteki.lista']);
        $this->assertArrayHasKey('slowniki.systemowe', $moze);
    }
}
