<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Narzedzia;
use App\Models\ToolFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Karta sprzętu: podpisy plików, zdjęcie na kartę i numer ewidencyjny UDT.
 *
 * Pliki pokazywały się pod nazwą z aparatu albo skanera i nie dało się jej
 * zmienić, a miniaturką było zawsze pierwsze wgrane zdjęcie — żeby ją
 * podmienić, trzeba było kasować zdjęcia i wgrywać na nowo po kolei.
 */
class PlikiSprzetuTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Narzedzia $sprzet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->biuro = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'biuro@mkl.pl', 'owner' => 2, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->sprzet = $this->narzedzie('Podest ruchomy');
    }

    private function narzedzie(string $nazwa): Narzedzia
    {
        return Narzedzia::create([
            'name' => $nazwa, 'numer_seryjny' => 'SN-'.$nazwa,
            'waznosc_badan' => '2027-01-01', 'ilosc_all' => 1, 'ilosc_budowa' => 0,
        ]);
    }

    private function plik(Narzedzia $sprzet, string $nazwa, string $typ = 'photo'): ToolFile
    {
        return ToolFile::create([
            'tool_id' => $sprzet->id, 'filename' => $nazwa, 'type' => $typ,
        ]);
    }

    private function karta(): array
    {
        return $this->actingAs($this->biuro)
            ->get('/narzedzia/'.$this->sprzet->id.'/edit')
            ->viewData('page')['props'];
    }

    public function test_plik_da_sie_podpisac_wlasna_nazwa(): void
    {
        $plik = $this->plik($this->sprzet, 'IMG_20240513_113245.jpg');

        $this->actingAs($this->biuro)
            ->put("/narzedzia/{$this->sprzet->id}/pliki/{$plik->id}", ['nazwa' => 'Tabliczka znamionowa'])
            ->assertRedirect();

        $this->assertSame('Tabliczka znamionowa', $this->karta()['photos'][0]['nazwa']);
        $this->assertSame('Tabliczka znamionowa', $this->karta()['photos'][0]['etykieta']);

        // Sam plik zostaje nietknięty — na jego nazwie stoją ścieżki i odnośniki.
        $this->assertSame('IMG_20240513_113245.jpg', $plik->fresh()->filename);
        $this->assertSame('IMG_20240513_113245.jpg', $this->karta()['photos'][0]['name']);
    }

    public function test_bez_podpisu_widac_nazwe_pliku(): void
    {
        $this->plik($this->sprzet, 'instrukcja.pdf', 'document');

        $dokument = $this->karta()['documents'][0];

        $this->assertNull($dokument['nazwa'], 'Puste pole znaczy "bez własnego podpisu".');
        $this->assertSame('instrukcja.pdf', $dokument['etykieta']);
    }

    public function test_wskazane_zdjecie_trafia_na_karte_i_do_miniaturki(): void
    {
        $pierwsze = $this->plik($this->sprzet, 'pierwsze.jpg');
        $drugie = $this->plik($this->sprzet, 'drugie.jpg');

        $this->actingAs($this->biuro)
            ->put("/narzedzia/{$this->sprzet->id}/pliki/{$drugie->id}", ['nazwa' => 'Cała maszyna', 'glowne' => true])
            ->assertRedirect();

        $this->assertFalse($pierwsze->fresh()->glowne);
        $this->assertTrue($drugie->fresh()->glowne);

        $this->actingAs($this->biuro)->get('/narzedzia')->assertOk()
            ->assertInertia(function (Assert $page) {
                $grupa = $page->toArray()['props']['grupy'][0];
                $this->assertStringContainsString('drugie.jpg', urldecode($grupa['photo']));
            });
    }

    public function test_wskazanie_nowego_zdjecia_zdejmuje_poprzednie(): void
    {
        // Główne musi być jedno, inaczej miniaturka znów zależy od kolejności.
        $a = $this->plik($this->sprzet, 'a.jpg');
        $b = $this->plik($this->sprzet, 'b.jpg');

        foreach ([$a, $b] as $zdjecie) {
            $this->actingAs($this->biuro)
                ->put("/narzedzia/{$this->sprzet->id}/pliki/{$zdjecie->id}", ['glowne' => true]);
        }

        $this->assertSame(1, ToolFile::where('tool_id', $this->sprzet->id)->where('glowne', true)->count());
        $this->assertTrue($b->fresh()->glowne);
    }

    public function test_dokument_nie_moze_byc_zdjeciem_glownym(): void
    {
        $dokument = $this->plik($this->sprzet, 'certyfikat.pdf', 'document');

        $this->actingAs($this->biuro)
            ->put("/narzedzia/{$this->sprzet->id}/pliki/{$dokument->id}", ['glowne' => true])
            ->assertSessionHas('error');

        $this->assertFalse($dokument->fresh()->glowne);
    }

    public function test_podpis_pliku_z_cudzej_karty_nie_przechodzi(): void
    {
        $obcy = $this->plik($this->narzedzie('Wiertarka'), 'obce.jpg');

        $this->actingAs($this->biuro)
            ->put("/narzedzia/{$this->sprzet->id}/pliki/{$obcy->id}", ['nazwa' => 'Podmienione'])
            ->assertNotFound();

        $this->assertNull($obcy->fresh()->nazwa);
    }

    public function test_plik_da_sie_usunac_z_karty(): void
    {
        Storage::fake('local');
        $plik = $this->plik($this->sprzet, 'do_kasacji.jpg');
        Storage::disk('local')->put('tools/'.$this->sprzet->id.'/do_kasacji.jpg', 'x');

        $this->actingAs($this->biuro)
            ->delete("/narzedzia/{$this->sprzet->id}/pliki/{$plik->id}")
            ->assertRedirect();

        $this->assertNull(ToolFile::find($plik->id));
        Storage::disk('local')->assertMissing('tools/'.$this->sprzet->id.'/do_kasacji.jpg');
    }

    public function test_numer_udt_zapisuje_sie_i_wraca_na_karte(): void
    {
        $this->actingAs($this->biuro)
            ->post('/narzedzia/'.$this->sprzet->id, [
                'narzedzia_typ_id' => null,
                'numer_seryjny' => 'SN-1',
                'numer_udt' => 'N3412000123',
                'ilosc_all' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('N3412000123', $this->sprzet->fresh()->numer_udt);
        $this->assertSame('N3412000123', $this->karta()['narzedzia']['numer_udt']);
    }

    public function test_sprzet_znajduje_sie_po_numerze_udt(): void
    {
        // Inspektor posługuje się swoim numerem, nie naszym seryjnym.
        $this->sprzet->update(['numer_udt' => 'N3412000123']);
        $this->narzedzie('Wiertarka');

        $this->actingAs($this->biuro)->get('/narzedzia?search=N34120')->assertOk()
            ->assertInertia(function (Assert $page) {
                $nazwy = collect($page->toArray()['props']['grupy'])->pluck('nazwa');
                $this->assertContains('Podest ruchomy', $nazwy);
                $this->assertNotContains('Wiertarka', $nazwy);
            });
    }
}
