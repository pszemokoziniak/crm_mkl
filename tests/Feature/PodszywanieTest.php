<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\PodszywanieController;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wejście administratora na cudze konto — do sprawdzania, co dana osoba
 * widzi w systemie. Powrót do siebie bez ponownego logowania.
 */
class PodszywanieTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $admin;
    private User $kierownik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->admin = $this->user(1, 'admin@mkl.pl');
        $this->kierownik = $this->user(3, 'kierownik@mkl.pl');
    }

    private function user(int $owner, string $email, array $nadpisz = []): User
    {
        return User::factory()->create(array_merge([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => $owner,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ], $nadpisz));
    }

    public function test_admin_wchodzi_na_cudze_konto(): void
    {
        $this->actingAs($this->admin)
            ->post('/users/'.$this->kierownik->id.'/wejdz-jako')
            ->assertRedirect('/');

        $this->assertSame($this->kierownik->id, auth()->id());
        $this->assertSame($this->admin->id, session(PodszywanieController::KLUCZ_SESJI));
    }

    public function test_po_wejsciu_widac_pasek_z_powrotem(): void
    {
        // Biuro, bo kierownikowi pulpit i tak podstawia listę jego budów.
        $biuro = $this->user(2, 'biuro@mkl.pl');

        $this->actingAs($this->admin)->post('/users/'.$biuro->id.'/wejdz-jako');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('podszywanie.kto', $biuro->name)
                ->etc()
            );
    }

    public function test_powrot_przywraca_konto_administratora(): void
    {
        $this->actingAs($this->admin)->post('/users/'.$this->kierownik->id.'/wejdz-jako');
        $this->assertSame($this->kierownik->id, auth()->id());

        $this->post('/wroc-do-siebie')->assertRedirect('/users');

        $this->assertSame($this->admin->id, auth()->id());
        $this->assertNull(session(PodszywanieController::KLUCZ_SESJI));
    }

    public function test_w_cudzej_sesji_obowiazuja_uprawnienia_tej_osoby(): void
    {
        $this->actingAs($this->admin)->get('/users')->assertOk();

        $this->post('/users/'.$this->kierownik->id.'/wejdz-jako');

        // Kierownik nie ma wstępu do użytkowników — admin w jego sesji też nie.
        $this->get('/users')->assertStatus(403);
    }

    public function test_biuro_i_kierownik_nie_wejda_na_cudze_konto(): void
    {
        $biuro = $this->user(2, 'biuro@mkl.pl');
        $cel = $this->user(3, 'cel@mkl.pl');

        $this->actingAs($biuro)
            ->post('/users/'.$cel->id.'/wejdz-jako')
            ->assertStatus(403);

        $this->actingAs($this->kierownik)
            ->post('/users/'.$cel->id.'/wejdz-jako')
            ->assertStatus(403);
    }

    public function test_nie_da_sie_wejsc_na_konto_zablokowane_ani_z_archiwum(): void
    {
        $zablokowany = $this->user(3, 'zablokowany@mkl.pl', ['active' => 0]);
        $usuniety = $this->user(3, 'usuniety@mkl.pl');
        $usuniety->delete();

        $this->actingAs($this->admin)->post('/users/'.$zablokowany->id.'/wejdz-jako');
        $this->assertSame($this->admin->id, auth()->id());

        $this->post('/users/'.$usuniety->id.'/wejdz-jako');
        $this->assertSame($this->admin->id, auth()->id());
    }

    public function test_nie_da_sie_wejsc_na_wlasne_konto(): void
    {
        $this->actingAs($this->admin)->post('/users/'.$this->admin->id.'/wejdz-jako');

        $this->assertSame($this->admin->id, auth()->id());
        $this->assertNull(session(PodszywanieController::KLUCZ_SESJI));
    }

    public function test_z_cudzego_konta_nie_da_sie_wejsc_na_kolejne(): void
    {
        $drugiAdmin = $this->user(1, 'admin2@mkl.pl');
        $trzeci = $this->user(3, 'trzeci@mkl.pl');

        $this->actingAs($this->admin)->post('/users/'.$drugiAdmin->id.'/wejdz-jako');
        $this->assertSame($drugiAdmin->id, auth()->id());

        // Drugi admin też jest adminem, więc trasa go wpuszcza — blokuje
        // dopiero sprawdzenie, że w sesji trwa już podszywanie.
        $this->post('/users/'.$trzeci->id.'/wejdz-jako');

        $this->assertSame($drugiAdmin->id, auth()->id());
        $this->assertSame($this->admin->id, session(PodszywanieController::KLUCZ_SESJI));
    }

    public function test_cudze_wymuszenie_zmiany_hasla_nie_zatrzymuje_admina(): void
    {
        $bezHasla = $this->user(2, 'bezhasla@mkl.pl', ['password_changed_at' => null]);

        // Bez podszywania takie konto ląduje na ekranie zmiany hasła...
        $this->actingAs($bezHasla)->get('/')->assertRedirect(route('password.expired'));

        // ...ale admin wchodzący na nie tylko po to, żeby zobaczyć widok,
        // nie ma tam po co utknąć.
        $this->actingAs($this->admin)->post('/users/'.$bezHasla->id.'/wejdz-jako');
        $this->get('/')->assertOk();
    }

    public function test_powrot_bez_podszywania_nic_nie_psuje(): void
    {
        $this->actingAs($this->admin)
            ->post('/wroc-do-siebie')
            ->assertRedirect('/');

        $this->assertSame($this->admin->id, auth()->id());
    }

    public function test_zwykla_sesja_nie_dostaje_paska(): void
    {
        $this->actingAs($this->admin)
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('podszywanie', null)->etc());
    }
}
