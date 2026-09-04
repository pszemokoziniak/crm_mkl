<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Konta w HRM tylko na firmowych adresach @mkl.pl.
 */
class DomenaEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->admin = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'admin@mkl.pl',
            'owner' => 1,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function dane(array $nadpisz = []): array
    {
        return array_merge([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@mkl.pl',
            'owner' => 3,
        ], $nadpisz);
    }

    public function test_adres_firmowy_przechodzi(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', $this->dane())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'jan.kowalski@mkl.pl']);
    }

    public function test_adres_spoza_domeny_jest_odrzucany(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', $this->dane(['email' => 'jan.kowalski@gmail.com']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'jan.kowalski@gmail.com']);
    }

    public function test_podobna_domena_nie_oszuka_walidacji(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', $this->dane(['email' => 'jan@mkl.pl.example.org']))
            ->assertSessionHasErrors('email');
    }

    public function test_duze_litery_sa_sprowadzane_do_malych(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', $this->dane(['email' => ' Jan.Kowalski@MKL.PL ']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'jan.kowalski@mkl.pl']);
    }

    public function test_edycja_tez_pilnuje_domeny(): void
    {
        $uzytkownik = User::factory()->create([
            'account_id' => $this->admin->account_id,
            'email' => 'piotr.nowak@mkl.pl',
            'owner' => 3,
            'active' => 1,
        ]);

        $this->actingAs($this->admin)
            ->put('/users/'.$uzytkownik->id, [
                'first_name' => 'Piotr',
                'last_name' => 'Nowak',
                'email' => 'piotr.nowak@wp.pl',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('piotr.nowak@mkl.pl', $uzytkownik->fresh()->email);
    }
}
