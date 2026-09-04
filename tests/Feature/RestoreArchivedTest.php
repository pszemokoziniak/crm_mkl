<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
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

    public function test_przywrocony_pracownik_przestaje_byc_zwolniony(): void
    {
        $contact = $this->zarchiwizowanyPracownik(Contact::STATUS_ZWOLNIONY);

        $this->actingAs($this->user(2))
            ->put('/contacts/'.$contact->id.'/restore')
            ->assertRedirect();

        $contact->refresh();

        $this->assertNull($contact->deleted_at);
        $this->assertSame(Contact::STATUS_AKTYWNY, $contact->status_zatrudnienia);
    }

    public function test_przywrocenie_nie_rusza_innego_statusu(): void
    {
        // Do archiwum trafia się przez "Zwolniony", ale gdyby kogoś usunięto
        // w trakcie urlopu, przywrócenie nie ma mu tego statusu zabierać.
        $contact = $this->zarchiwizowanyPracownik(Contact::STATUS_URLOP);

        $this->actingAs($this->user(2))
            ->put('/contacts/'.$contact->id.'/restore')
            ->assertRedirect();

        $this->assertSame(Contact::STATUS_URLOP, $contact->fresh()->status_zatrudnienia);
    }

    public function test_przywrocony_pracownik_wraca_na_liste(): void
    {
        $contact = $this->zarchiwizowanyPracownik(Contact::STATUS_ZWOLNIONY);
        $biuro = $this->user(2);

        $this->actingAs($biuro)->put('/contacts/'.$contact->id.'/restore');

        $odpowiedz = $this->actingAs($biuro)->get('/contacts');
        $nazwiska = collect($odpowiedz->viewData('page')['props']['contacts']['data'])->pluck('last_name');

        $this->assertContains('Ambroziak', $nazwiska);
    }

    public function test_historia_pobytow_wraca_razem_z_pracownikiem(): void
    {
        $contact = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Mateusz',
            'last_name' => 'Ambroziak',
            'status_zatrudnienia' => Contact::STATUS_ZWOLNIONY,
        ]);
        $budowa = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '434_Valmet']);

        $pobyt = ContactWorkDate::create([
            'contact_id' => $contact->id,
            'organization_id' => $budowa->id,
            'start' => '2026-01-01',
            'end' => '2026-06-30',
        ]);

        $biuro = $this->user(2);

        // Archiwizacja przez kartę pracownika — tą samą drogą co w systemie.
        $this->actingAs($biuro)->post('/contacts/'.$contact->id, [
            'first_name' => 'Mateusz',
            'last_name' => 'Ambroziak',
            'birth_date' => '1990-05-05',
            'pesel' => '90050512345',
            'work_start' => '2026-01-01',
            'work_end' => '2026-12-31',
            'status_zatrudnienia' => Contact::STATUS_ZWOLNIONY,
            'archive' => true,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(Contact::withTrashed()->find($contact->id)->deleted_at);
        $this->assertSame(0, ContactWorkDate::where('contact_id', $contact->id)->count());

        $this->actingAs($biuro)->put('/contacts/'.$contact->id.'/restore');

        $wrocone = ContactWorkDate::where('contact_id', $contact->id)->get();
        $this->assertCount(1, $wrocone);
        $this->assertSame($pobyt->id, $wrocone->first()->id);
        $this->assertSame('2026-06-30', $wrocone->first()->end);
    }

    public function test_pobyt_skasowany_wczesniej_nie_wraca(): void
    {
        $contact = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Mateusz',
            'last_name' => 'Ambroziak',
            'status_zatrudnienia' => Contact::STATUS_ZWOLNIONY,
        ]);
        $budowa = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '434_Valmet']);

        // Pomyłkowe przypisanie skasowane tydzień wcześniej w zakładce budowy.
        $skasowany = ContactWorkDate::create([
            'contact_id' => $contact->id,
            'organization_id' => $budowa->id,
            'start' => '2025-01-01',
            'end' => '2025-02-01',
        ]);
        $skasowany->delete();
        $skasowany->forceFill(['deleted_at' => now()->subWeek()])->saveQuietly();

        $contact->delete();
        ContactWorkDate::where('contact_id', $contact->id)->delete();

        $this->actingAs($this->user(2))->put('/contacts/'.$contact->id.'/restore');

        $this->assertSame(0, ContactWorkDate::where('contact_id', $contact->id)->count());
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

    private function zarchiwizowanyPracownik(?string $status = null): Contact
    {
        $contact = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Mateusz',
            'last_name' => 'Ambroziak',
            'status_zatrudnienia' => $status ?? Contact::STATUS_ZWOLNIONY,
        ]);

        $contact->delete();

        return $contact;
    }
}
