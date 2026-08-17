<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zakres uprawnień do przywracania z archiwum — ten sam, który
 * TrashedMessage pokazuje w interfejsie (admin + biuro).
 */
class RestoreArchivedTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
    }

    public function test_biuro_przywraca_pracownika_z_archiwum(): void
    {
        $contact = $this->zarchiwizowanyPracownik();

        $this->actingAs($this->user(2))
            ->put('/contacts/'.$contact->id.'/restore')
            ->assertRedirect();

        $this->assertNull($contact->fresh()->deleted_at);
    }

    public function test_admin_przywraca_pracownika_z_archiwum(): void
    {
        $contact = $this->zarchiwizowanyPracownik();

        $this->actingAs($this->user(1))
            ->put('/contacts/'.$contact->id.'/restore')
            ->assertRedirect();

        $this->assertNull($contact->fresh()->deleted_at);
    }

    public function test_kierownik_nie_przywraca_pracownika(): void
    {
        $contact = $this->zarchiwizowanyPracownik();

        $this->actingAs($this->user(3))
            ->put('/contacts/'.$contact->id.'/restore')
            ->assertForbidden();

        $this->assertNotNull($contact->fresh()->deleted_at);
    }

    public function test_karta_zarchiwizowanego_pracownika_sie_otwiera(): void
    {
        $contact = $this->zarchiwizowanyPracownik();

        $this->actingAs($this->user(1))
            ->get('/contacts/'.$contact->id.'/edit')
            ->assertOk();
    }

    private function user(int $owner): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'rola'.$owner.'@example.com',
            'owner' => $owner,
            'active' => 1,
        ]);
    }

    private function zarchiwizowanyPracownik(): Contact
    {
        $contact = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Mateusz',
            'last_name' => 'Ambroziak',
        ]);

        $contact->delete();

        return $contact;
    }
}
