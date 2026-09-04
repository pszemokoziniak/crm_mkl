<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Powiązanie konta użytkownika z pracownikiem (Użytkownicy → Profil).
 * Zakres: biuro i admin — tak jak middleware na trasach.
 */
class PowiazanieUserPracownikTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        // Funkcje z FK na contacts — 1 i 6 to te, które można powiązać z kontem.
        DB::table('funkcjas')->insert([
            ['id' => 1, 'name' => 'Kierownik Budowy'],
            ['id' => 2, 'name' => 'monter konstrukcji stalowych'],
            ['id' => 6, 'name' => 'Inżynier budowy'],
        ]);
    }

    public function test_biuro_laczy_uzytkownika_z_pracownikiem(): void
    {
        $biuro = $this->user(2, 'biuro@mkl.pl');
        $kierownik = $this->user(3, 'kierownik@mkl.pl');
        $pracownik = $this->pracownik('Kaczmarski', 'Sylwester');

        $this->actingAs($biuro)
            ->put('/users/'.$kierownik->id, [
                'first_name' => $kierownik->first_name,
                'last_name' => $kierownik->last_name,
                'email' => $kierownik->email,
                'contact_id' => $pracownik->id,
            ])
            ->assertRedirect();

        $this->assertSame($kierownik->id, $pracownik->fresh()->user_id);
    }

    public function test_zmiana_powiazania_zdejmuje_poprzednie(): void
    {
        $biuro = $this->user(2, 'biuro@mkl.pl');
        $konto = $this->user(3, 'kierownik@mkl.pl');

        $stary = $this->pracownik('Poprzedni', 'Jan');
        $stary->update(['user_id' => $konto->id]);
        $nowy = $this->pracownik('Nowy', 'Adam');

        $this->actingAs($biuro)->put('/users/'.$konto->id, [
            'first_name' => $konto->first_name,
            'last_name' => $konto->last_name,
            'email' => $konto->email,
            'contact_id' => $nowy->id,
        ]);

        $this->assertSame($konto->id, $nowy->fresh()->user_id);
        // Jedno konto może wskazywać tylko na jednego pracownika.
        $this->assertNull($stary->fresh()->user_id);
    }

    public function test_kierownik_nie_powiaze_konta_z_pracownikiem(): void
    {
        $kierownik = $this->user(3, 'kierownik@mkl.pl');
        $pracownik = $this->pracownik('Kaczmarski', 'Sylwester');

        // Własny profil kierownik może edytować, ale powiązania już nie ruszy.
        $this->actingAs($kierownik)->put('/users/'.$kierownik->id, [
            'first_name' => 'Zmienione',
            'last_name' => $kierownik->last_name,
            'email' => $kierownik->email,
            'contact_id' => $pracownik->id,
        ]);

        $this->assertNull($pracownik->fresh()->user_id);
        $this->assertSame('Zmienione', $kierownik->fresh()->first_name);
    }

    public function test_profil_podaje_liste_pracownikow_do_polaczenia(): void
    {
        $biuro = $this->user(2, 'biuro@mkl.pl');
        $konto = $this->user(3, 'kierownik@mkl.pl');

        $this->pracownik('Wolny', 'Kierownik');
        $this->pracownik('Zajety', 'Kierownik')->update(['user_id' => $biuro->id]);
        $this->pracownik('Monter', 'Zwykly', 2);

        $this->actingAs($biuro)
            ->get('/users/'.$konto->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Edit')
                // Tylko kierownicy i inżynierowie bez konta.
                ->has('contacts', 1)
                ->where('contacts.0.last_name', 'Wolny')
            );
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => $owner,
            'active' => 1,
        ]);
    }

    private function pracownik(string $nazwisko, string $imie, int $funkcjaId = 1): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => $imie,
            'last_name' => $nazwisko,
            'funkcja_id' => $funkcjaId,
        ]);
    }
}
