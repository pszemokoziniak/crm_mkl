<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Zakładanie konta i komunikat o zajętym adresie.
 *
 * Zgłoszenie: "przy próbie dodania nowego użytkownika wyskakuje błąd".
 * Blokada była słuszna — konto z tym adresem leżało w koszu, a adres jest
 * w bazie unikalny. Mylił komunikat: "Nazwa użyta" nie mówiło, że wystarczy
 * przywrócić konto tej samej osoby, więc wyglądało to jak awaria.
 */
class ZakladanieKontaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->admin = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'admin@mkl.pl',
            'owner' => Role::ADMIN->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function zaloz(string $email): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post('/users', [
            'first_name' => 'Marcin', 'last_name' => 'Redosz',
            'email' => $email, 'owner' => (string) Role::BIURO->value,
        ]);
    }

    public function test_filtr_wyswietlania_rozroznia_aktualne_archiwum_i_wszystko(): void
    {
        // Podpisy na ekranie: Aktualne / Archiwum / Wszystko. Wartości wysyłane
        // na serwer to kolejno brak / only / with — łatwo je przy okazji zamienić.
        $czynne = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'czynny@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
        $wKoszu = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'wkoszu@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
        $wKoszu->delete();

        $adresy = function (array $filtry) {
            $adres = '/users'.($filtry ? '?'.http_build_query($filtry) : '');

            return collect($this->actingAs($this->admin)->get($adres)->assertOk()
                ->viewData('page')['props']['users'])->pluck('email');
        };

        $this->assertContains('czynny@mkl.pl', $adresy([]));
        $this->assertNotContains('wkoszu@mkl.pl', $adresy([]), 'Aktualne: bez archiwum.');

        $this->assertContains('wkoszu@mkl.pl', $adresy(['trashed' => 'only']));
        $this->assertNotContains('czynny@mkl.pl', $adresy(['trashed' => 'only']), 'Archiwum: samo archiwum.');

        $wszystko = $adresy(['trashed' => 'with']);
        $this->assertContains('czynny@mkl.pl', $wszystko);
        $this->assertContains('wkoszu@mkl.pl', $wszystko);
    }

    public function test_adres_konta_z_kosza_mowi_o_przywroceniu(): void
    {
        $usuniete = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'marcin.redosz@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
        $usuniete->delete();

        $this->zaloz('marcin.redosz@mkl.pl')
            ->assertSessionHasErrors(['email' => 'Konto z tym adresem jest w koszu (usunięte '
                .$usuniete->fresh()->deleted_at->format('d.m.Y').'). Przywróć je zamiast zakładać nowe.']);

        // Blokada zostaje — drugiego konta z tym adresem nie zakładamy.
        $this->assertSame(1, User::withTrashed()->where('email', 'marcin.redosz@mkl.pl')->count());
    }

    public function test_adres_czynnego_konta_mowi_ze_juz_istnieje(): void
    {
        User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'marcin.redosz@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->zaloz('marcin.redosz@mkl.pl')
            ->assertSessionHasErrors(['email' => 'Konto z tym adresem już istnieje.']);
    }

    public function test_wolny_adres_przechodzi(): void
    {
        $this->zaloz('marcin.redosz@mkl.pl')->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'marcin.redosz@mkl.pl']);
    }

    public function test_edycja_wlasnego_adresu_nie_blokuje_sie_o_siebie(): void
    {
        $konto = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'jan.kowalski@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($this->admin)->put("/users/{$konto->id}", [
            'first_name' => 'Jan', 'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@mkl.pl', 'owner' => (string) Role::BIURO->value,
        ])->assertSessionHasNoErrors();
    }
}
