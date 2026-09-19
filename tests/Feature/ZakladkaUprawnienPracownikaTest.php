<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Zakładka „Uprawnienia” w karcie pracownika i ekran „Uprawnienia ról”
 * w Ustawieniach dzieliły jedną nazwę komponentu Inertia — matryca ról
 * nadpisała listę uprawnień pracownika. Teraz to dwa różne ekrany.
 */
class ZakladkaUprawnienPracownikaTest extends TestCase
{
    use RefreshDatabase;

    public function test_zakladka_pracownika_to_lista_jego_uprawnien_a_nie_matryca_rol(): void
    {
        $accountId = Account::create(['name' => 'MKL'])->id;
        $contact = Contact::create(['account_id' => $accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $biuro = User::factory()->create([
            'account_id' => $accountId, 'owner' => 2, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($biuro)
            ->get('/contacts/'.$contact->id.'/uprawnienia')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Uprawnienia/Index')
                ->has('uprawnienias')
                ->where('contact.id', $contact->id)
            );
    }
}
