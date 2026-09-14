<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\CreateUserPassword;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Zakładanie konta: użytkownik dostaje hasło mailem i ma się nim zalogować.
 *
 * Kolumna `active` jest NOT NULL i nie miała wartości domyślnej, a zapis
 * zakładający konto jej nie ustawiał — baza wstawiała zero, czyli konto
 * zablokowane. Każdy nowy użytkownik po wpisaniu hasła z maila widział
 * "Konto zablokowane" i trzeba go było odblokowywać ręcznie.
 */
class ZakladanieKontaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->admin = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'admin@mkl.pl',
            'owner' => 1,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    /** Zakłada konto i zwraca hasło, które poszło mailem. */
    private function zalozKonto(string $email = 'nowy.uzytkownik@mkl.pl'): string
    {
        Mail::fake();

        $this->actingAs($this->admin)
            ->post('/users', [
                'first_name' => 'Karol',
                'last_name' => 'Sidorowicz',
                'email' => $email,
                'owner' => 2,
            ])
            ->assertRedirect();

        $haslo = null;
        Mail::assertSent(CreateUserPassword::class, function (CreateUserPassword $mail) use (&$haslo) {
            $haslo = $mail->password;

            return true;
        });

        return $haslo;
    }

    /**
     * Samo `/logout` nie wystarczy: `actingAs` trzyma admina w kontenerze
     * niezależnie od sesji, więc kolejne żądanie dalej byłoby jego i test
     * sprawdzałby cudze logowanie zamiast nowego konta.
     */
    private function wyloguj(): void
    {
        $this->post('/logout');
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    public function test_nowe_konto_nie_jest_zablokowane(): void
    {
        $this->zalozKonto();

        $this->assertSame(1, (int) User::where('email', 'nowy.uzytkownik@mkl.pl')->value('active'));
    }

    public function test_haslo_z_maila_wpuszcza_do_systemu(): void
    {
        $haslo = $this->zalozKonto();

        $this->wyloguj();

        $this->post('/login', ['email' => 'nowy.uzytkownik@mkl.pl', 'password' => $haslo])
            ->assertRedirect()
            ->assertSessionMissing('error');

        $this->assertAuthenticatedAs(User::where('email', 'nowy.uzytkownik@mkl.pl')->first());
    }

    public function test_pierwsze_logowanie_dalej_wymusza_zmiane_hasla(): void
    {
        // Odblokowanie konta nie może znieść wymogu zmiany hasła startowego.
        $haslo = $this->zalozKonto();
        $this->wyloguj();
        $this->post('/login', ['email' => 'nowy.uzytkownik@mkl.pl', 'password' => $haslo]);

        $this->get('/')->assertRedirect('/password/expired');

        $konto = User::where('email', 'nowy.uzytkownik@mkl.pl')->first();
        $this->assertNull($konto->password_changed_at);
        $this->assertTrue(Hash::check($haslo, $konto->password), 'Mailem idzie to samo hasło, które zapisujemy.');
    }

    public function test_konto_zapisane_bez_wskazania_jest_aktywne(): void
    {
        // Druga linia obrony: kolumna ma wartość domyślną, więc żadna inna
        // droga zapisu nie stworzy po cichu konta, którym nie da się wejść.
        $konto = $this->admin->account->users()->create([
            'first_name' => 'Aneta',
            'last_name' => 'Woźniak',
            'email' => 'aneta.wozniak@mkl.pl',
            'password' => 'CokolwiekInnego1!',
            'owner' => 2,
        ]);

        $this->assertSame(1, (int) User::find($konto->id)->active);
    }

    public function test_zablokowane_konto_dalej_nie_wpuszcza(): void
    {
        $haslo = $this->zalozKonto();
        $konto = User::where('email', 'nowy.uzytkownik@mkl.pl')->first();
        $konto->update(['active' => 0]);

        $this->wyloguj();

        $this->post('/login', ['email' => 'nowy.uzytkownik@mkl.pl', 'password' => $haslo])
            ->assertSessionHas('error', 'Konto zablokowane.');

        $this->assertGuest();
    }
}
