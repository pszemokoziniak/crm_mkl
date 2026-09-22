<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Zdjęcie pracownika: pobranie oryginału i usunięcie (kasuje plik i wpis).
 */
class ZdjeciePracownikaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId, 'owner' => 2, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function pracownikZeZdjeciem(): Contact
    {
        Storage::fake('local');
        Storage::put('contacts/foto.png', 'PNGDATA');

        return Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski',
            'photo_path' => 'contacts/foto.png',
        ]);
    }

    public function test_pobranie_oddaje_plik_pod_nazwiskiem(): void
    {
        $c = $this->pracownikZeZdjeciem();

        $this->actingAs($this->biuro)
            ->get('/contacts/'.$c->id.'/zdjecie')
            ->assertOk()
            ->assertDownload('Kowalski Jan.png');
    }

    public function test_usuniecie_kasuje_plik_i_wpis(): void
    {
        $c = $this->pracownikZeZdjeciem();

        $this->actingAs($this->biuro)
            ->delete('/contacts/'.$c->id.'/zdjecie')
            ->assertRedirect();

        $this->assertNull($c->fresh()->photo_path);
        Storage::disk('local')->assertMissing('contacts/foto.png');

        // Po usunięciu nie ma czego pobrać.
        $this->actingAs($this->biuro)->get('/contacts/'.$c->id.'/zdjecie')->assertNotFound();
    }

    public function test_brak_zdjecia_to_404(): void
    {
        $c = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => 'Bez']);

        $this->actingAs($this->biuro)->get('/contacts/'.$c->id.'/zdjecie')->assertNotFound();
    }
}
