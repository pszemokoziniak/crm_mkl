<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Ustawienia → Uprawnienia ról: tylko admin, a to, co zapisze, od razu
 * obowiązuje na trasach i w menu.
 */
class EkranUprawnienTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => $email,
            'owner' => $owner, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_ekran_widzi_tylko_admin(): void
    {
        $this->actingAs($this->user(2, 'biuro@mkl.pl'))->get('/uprawnienia-rol')->assertForbidden();
        $this->actingAs($this->user(4, 'kier@mkl.pl'))->get('/uprawnienia-rol')->assertForbidden();

        $this->actingAs($this->user(1, 'admin@mkl.pl'))->get('/uprawnienia-rol')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Uprawnienia/Index')
                ->has('obszary')
                ->has('role', 6)
                ->where('role.5.nazwa', 'Administrator')
                ->where('role.5.edytowalna', false)
                ->where('role.0.nadpisana', false)
            );
    }

    public function test_zapis_z_ekranu_od_razu_obowiazuje_na_trasie_i_w_menu(): void
    {
        $admin = $this->user(1, 'admin@mkl.pl');
        $biuro = $this->user(2, 'biuro@mkl.pl');

        $this->actingAs($biuro)->get('/contacts')->assertOk();

        $this->actingAs($admin)
            ->put('/uprawnienia-rol/2', ['uprawnienia' => ['budowy.podglad', 'sprzet.obsluga']])
            ->assertRedirect('/uprawnienia-rol');

        $this->actingAs($biuro)->get('/contacts')->assertForbidden();
        $this->actingAs($biuro)->get('/narzedzia')->assertOk();
        $this->assertFalse($biuro->fresh()->permissions['moze']['kartoteki.lista']);

        $this->actingAs($admin)->delete('/uprawnienia-rol/2')->assertRedirect('/uprawnienia-rol');
        $this->actingAs($biuro)->get('/contacts')->assertOk();
    }

    public function test_nieznane_uprawnienie_i_admin_sa_odrzucane(): void
    {
        $admin = $this->user(1, 'admin@mkl.pl');

        $this->actingAs($admin)->from('/uprawnienia-rol')
            ->put('/uprawnienia-rol/2', ['uprawnienia' => ['nie.ma']])
            ->assertSessionHasErrors('uprawnienia.0');

        $this->actingAs($admin)->put('/uprawnienia-rol/1', ['uprawnienia' => []])->assertStatus(422);
        $this->actingAs($admin)->put('/uprawnienia-rol/9', ['uprawnienia' => []])->assertNotFound();
    }
}
