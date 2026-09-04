<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Funkcja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kadry (uprawnienie "biuro") prowadzą słownik stanowisk same — to on
 * decyduje, kto trafia do zakładki Kierownicy/Inżynierowie i kto może
 * wejść do kierownictwa budowy. Pozostałe słowniki zostają przy adminie.
 */
class KadryStanowiskaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $kadry;
    private User $kierownik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->kadry = $this->user(2, 'kadry@mkl.pl');
        $this->kierownik = $this->user(3, 'kierownik@mkl.pl');
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => $owner,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_kadry_widza_liste_stanowisk(): void
    {
        $this->actingAs($this->kadry)->get('/funkcja')->assertOk();
    }

    public function test_kadry_dodaja_stanowisko(): void
    {
        $this->actingAs($this->kadry)
            ->post('/funkcja', ['name' => 'Operator koparki', 'kierownictwo' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('funkcjas', ['name' => 'Operator koparki']);
    }

    public function test_kadry_przestawiaja_znacznik_kierownictwa(): void
    {
        $funkcja = Funkcja::create(['name' => 'Koordynator ds. Realizacji', 'kierownictwo' => false]);

        $this->actingAs($this->kadry)
            ->put('/funkcja/'.$funkcja->id, ['name' => $funkcja->name, 'kierownictwo' => true])
            ->assertRedirect();

        $this->assertTrue($funkcja->fresh()->kierownictwo);
    }

    public function test_kierownik_nadal_nie_ma_dostepu(): void
    {
        $this->actingAs($this->kierownik)->get('/funkcja')->assertStatus(403);
    }

    public function test_pozostale_slowniki_zostaja_przy_adminie(): void
    {
        foreach (['/badaniaTyp', '/bhpTyp', '/jezykTyp', '/krajTyp', '/dokumentyTyp', '/narzedziaTyp'] as $adres) {
            $this->actingAs($this->kadry)->get($adres)->assertStatus(403);
        }
    }

    public function test_kadry_wchodza_na_strone_ustawien(): void
    {
        $this->actingAs($this->kadry)->get('/tools')->assertOk();
    }
}
