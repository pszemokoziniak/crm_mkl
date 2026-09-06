<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Logowanie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Rejestr logowań: każde wejście i każda nieudana próba zostawiają ślad
 * z adresem IP. Podgląd tylko dla administratora, wpisy starsze niż rok
 * kasuje harmonogram.
 */
class RejestrLogowanTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $pracownik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->pracownik = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'kowalski@mkl.pl',
            'password' => Hash::make('TajneHaslo123!'),
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_udane_logowanie_zostawia_slad(): void
    {
        $this->post('/login', ['email' => 'kowalski@mkl.pl', 'password' => 'TajneHaslo123!'])
            ->assertRedirect();

        $wpis = Logowanie::firstOrFail();

        $this->assertTrue($wpis->udane);
        $this->assertSame($this->pracownik->id, $wpis->user_id);
        $this->assertSame('kowalski@mkl.pl', $wpis->email);
        $this->assertNotNull($wpis->ip);
        $this->assertNull($wpis->powod);
    }

    public function test_bledne_haslo_tez_zostawia_slad(): void
    {
        $this->post('/login', ['email' => 'kowalski@mkl.pl', 'password' => 'zle-haslo'])
            ->assertSessionHasErrors();

        $wpis = Logowanie::firstOrFail();

        $this->assertFalse($wpis->udane);
        $this->assertSame(Logowanie::POWOD_ZLE_HASLO, $wpis->powod);
        $this->assertSame($this->pracownik->id, $wpis->user_id);
    }

    public function test_proba_na_nieistniejace_konto(): void
    {
        $this->post('/login', ['email' => 'obcy@example.com', 'password' => 'cokolwiek1!']);

        $wpis = Logowanie::firstOrFail();

        $this->assertFalse($wpis->udane);
        $this->assertSame(Logowanie::POWOD_BRAK_KONTA, $wpis->powod);
        $this->assertNull($wpis->user_id);
        $this->assertSame('obcy@example.com', $wpis->email);
    }

    public function test_proba_na_zablokowane_konto(): void
    {
        $this->pracownik->update(['active' => 0]);

        $this->post('/login', ['email' => 'kowalski@mkl.pl', 'password' => 'TajneHaslo123!']);

        $this->assertSame(Logowanie::POWOD_ZABLOKOWANE, Logowanie::firstOrFail()->powod);
    }

    public function test_rejestr_oglada_tylko_administrator(): void
    {
        $admin = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'admin@mkl.pl',
            'owner' => 1,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($admin)->get('/logowania')->assertOk();
        $this->actingAs($this->pracownik)->get('/logowania')->assertStatus(403);
    }

    public function test_filtr_pokazuje_same_nieudane(): void
    {
        $this->post('/login', ['email' => 'kowalski@mkl.pl', 'password' => 'zle-haslo']);
        $this->post('/login', ['email' => 'kowalski@mkl.pl', 'password' => 'TajneHaslo123!']);

        $admin = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'admin@mkl.pl',
            'owner' => 1,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $odpowiedz = $this->actingAs($admin)->get('/logowania?wynik=nieudane');
        $wpisy = $odpowiedz->viewData('page')['props']['logowania']['data'];

        $this->assertCount(1, $wpisy);
        $this->assertFalse($wpisy[0]['udane']);
    }

    public function test_stare_wpisy_sa_kasowane(): void
    {
        Logowanie::create([
            'user_id' => $this->pracownik->id, 'email' => 'kowalski@mkl.pl',
            'udane' => true, 'ip' => '10.0.0.1', 'created_at' => now()->subMonths(13),
        ]);
        Logowanie::create([
            'user_id' => $this->pracownik->id, 'email' => 'kowalski@mkl.pl',
            'udane' => true, 'ip' => '10.0.0.1', 'created_at' => now()->subMonths(2),
        ]);

        $this->artisan('logowania:posprzataj')->assertExitCode(0);

        $this->assertSame(1, Logowanie::count());
    }
}
