<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Adres /img wydawał każdy plik ze storage bez logowania — wystarczyło znać
 * ścieżkę. Szły tamtędy zdjęcia pracowników, kont i sprzętu, a sam katalog
 * skanów dokumentów też był osiągalny.
 */
class DostepDoPlikowTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->biuro = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'biuro@mkl.pl', 'owner' => 2, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('tools/32/zdjecie.jpg', 'udawane-zdjecie');
        Storage::disk('local')->put('contacts/7/twarz.jpg', 'udawane-zdjecie');
        Storage::disk('local')->put('documents/7/dowod.pdf', 'udawany-skan');
    }

    public function test_bez_logowania_plik_ze_storage_nie_wychodzi(): void
    {
        $this->get('/img/tools/32/zdjecie.jpg')->assertNotFound();
        $this->get('/img/contacts/7/twarz.jpg')->assertNotFound();
    }

    public function test_zalogowany_widzi_zdjecia_jak_dotad(): void
    {
        $this->actingAs($this->biuro)->get('/img/tools/32/zdjecie.jpg')->assertOk();
        $this->actingAs($this->biuro)->get('/img/contacts/7/twarz.jpg')->assertOk();
    }

    public function test_skany_dokumentow_nie_wychodza_ta_droga_nawet_po_zalogowaniu(): void
    {
        // Mają własny adres, który sprawdza, czy ten pracownik należy do
        // pytającego. Tędy obchodziłoby się to sprawdzenie.
        $this->actingAs($this->biuro)->get('/img/documents/7/dowod.pdf')->assertNotFound();
    }

    public function test_sciezka_nie_wyprowadza_poza_katalog_danych(): void
    {
        $this->actingAs($this->biuro)->get('/img/..%2F..%2F.env')->assertNotFound();
    }

    public function test_logo_z_katalogu_publicznego_zostaje_jawne(): void
    {
        // Widnieje na ekranie logowania, czyli zanim ktokolwiek się zaloguje.
        $this->assertFileExists(public_path('img/MKL-BAU.png'));
        $this->get('/img/img/MKL-BAU.png')->assertOk();
    }
}
