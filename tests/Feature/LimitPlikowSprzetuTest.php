<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\NarzedziaController;
use App\Models\Account;
use App\Models\Narzedzia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Dodawanie sprzętu z plikami. Serwer ma swój limit wielkości pliku
 * i aplikacja nie może obiecywać więcej, niż on przyjmie — inaczej
 * zapis kończy się ciszą zamiast komunikatem.
 */
class LimitPlikowSprzetuTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->biuro = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_formularz_podaje_limit_z_konfiguracji_serwera(): void
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/narzedzia/create');
        $odpowiedz->assertOk();

        $limit = $odpowiedz->viewData('page')['props']['limitPlikuMb'];

        $this->assertSame(NarzedziaController::limitPlikuMb(), $limit);
        $this->assertGreaterThan(0, $limit);
    }

    public function test_limit_nie_przekracza_tego_co_przyjmie_php(): void
    {
        $limitMb = NarzedziaController::limitPlikuMb();
        $upload = (int) ini_get('upload_max_filesize');

        // Wartość z ini bywa zapisana z jednostką — bierzemy tylko liczbę,
        // a i tak limit aplikacji ma być nie większy.
        if ($upload > 0) {
            $this->assertLessThanOrEqual($upload, $limitMb);
        }
    }

    public function test_sprzet_z_malym_dokumentem_zapisuje_sie(): void
    {
        $this->actingAs($this->biuro)
            ->post('/narzedzia', [
                'new_typ_name' => 'Kontener 9m',
                'numer_seryjny' => 'SN-DOK',
                'ilosc_all' => 1,
                'documents' => [UploadedFile::fake()->create('gwarancja.pdf', 100)],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNotNull(Narzedzia::firstWhere('numer_seryjny', 'SN-DOK'));
    }

    public function test_za_duzy_dokument_konczy_sie_bledem_a_nie_cisza(): void
    {
        $zaDuzy = (NarzedziaController::limitPlikuMb() * 1024) + 500;

        $this->actingAs($this->biuro)
            ->post('/narzedzia', [
                'new_typ_name' => 'Kontener 9m',
                'numer_seryjny' => 'SN-DUZY',
                'ilosc_all' => 1,
                'documents' => [UploadedFile::fake()->create('skan.pdf', $zaDuzy)],
            ])
            ->assertSessionHasErrors('documents.0');

        $this->assertNull(Narzedzia::firstWhere('numer_seryjny', 'SN-DUZY'));
    }

    public function test_karta_sprzetu_tez_zna_limit(): void
    {
        $sprzet = Narzedzia::create([
            'name' => 'Kontener 6m',
            'numer_seryjny' => 'SN-1',
            'ilosc_all' => 1,
            'ilosc_budowa' => 0,
        ]);

        $odpowiedz = $this->actingAs($this->biuro)->get('/narzedzia/'.$sprzet->id.'/edit');

        $this->assertSame(NarzedziaController::limitPlikuMb(), $odpowiedz->viewData('page')['props']['limitPlikuMb']);
    }
}
