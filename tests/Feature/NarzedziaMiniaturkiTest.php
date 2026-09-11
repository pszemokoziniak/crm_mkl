<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\GrupaSprzetu;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\ToolFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Miniaturki zdjęć na liście sprzętu.
 */
class NarzedziaMiniaturkiTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->biuro = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'biuro@example.com',
            'owner' => 2,
            'active' => 1,
        ]);
    }

    public function test_lista_podaje_miniaturke_pierwszego_zdjecia(): void
    {
        $narzedzie = $this->narzedzie('Wiertarka');
        $this->plik($narzedzie, 'pierwsze.jpg', 'photo');
        $this->plik($narzedzie, 'drugie.jpg', 'photo');
        // Dokument nie jest zdjęciem i nie może trafić do miniaturki.
        $this->plik($narzedzie, 'instrukcja.pdf', 'document');

        $this->actingAs($this->biuro)
            ->get('/narzedzia')
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($narzedzie) {
                // Lista jest zgrupowana po rodzaju — miniaturka wisi przy grupie.
                $grupa = $page->toArray()['props']['grupy'][0];

                $this->assertStringContainsString('tools/'.$narzedzie->id.'/pierwsze.jpg', urldecode($grupa['photo']));
                $this->assertStringContainsString('w=96', $grupa['photo']);
            });
    }

    public function test_kategoria_nie_bierze_zdjecia_jednego_ze_swoich_modeli(): void
    {
        // "Zwyżka" zbiera cztery różne maszyny, więc zdjęcie tej, którą ktoś
        // sfotografował pierwszą, podpisywało całą kategorię.
        $grupa = GrupaSprzetu::zNazwy('Zwyżka');
        $jlg = NarzedziaTyp::create(['name' => 'JLG X33J Plus', 'grupa_id' => $grupa->id]);
        $manitou = NarzedziaTyp::create(['name' => 'Manitou 160 ATJ+', 'grupa_id' => $grupa->id]);

        $zeZdjeciem = $this->narzedzie('JLG X33J Plus', $jlg->id);
        $this->plik($zeZdjeciem, 'jlg.jpg', 'photo');
        $this->narzedzie('Manitou 160 ATJ+', $manitou->id);

        $this->actingAs($this->biuro)->get('/narzedzia')->assertOk()
            ->assertInertia(function (Assert $page) {
                $kategoria = collect($page->toArray()['props']['grupy'])->firstWhere('nazwa', 'Zwyżka');

                $this->assertNull($kategoria['photo'], 'Wiersz kategorii pokazuje ikonę, nie jedną z maszyn.');

                // Przy samym modelu zdjęcie zostaje — tam opisuje, co trzeba.
                $model = collect($kategoria['modele'])->firstWhere('nazwa', 'JLG X33J Plus');
                $this->assertStringContainsString('jlg.jpg', urldecode($model['photo']));
            });
    }

    public function test_sprzet_bez_kategorii_dalej_pokazuje_swoje_zdjecie(): void
    {
        // Taki wiersz to sam model, nie zbiorcza kategoria.
        $narzedzie = $this->narzedzie('Zagęszczarka');
        $this->plik($narzedzie, 'zageszczarka.jpg', 'photo');

        $this->actingAs($this->biuro)->get('/narzedzia')->assertOk()
            ->assertInertia(function (Assert $page) {
                $wiersz = collect($page->toArray()['props']['grupy'])->firstWhere('nazwa', 'Zagęszczarka');

                $this->assertFalse($wiersz['ma_modele']);
                $this->assertStringContainsString('zageszczarka.jpg', urldecode($wiersz['photo']));
            });
    }

    public function test_sprzet_bez_zdjecia_ma_puste_pole(): void
    {
        $this->narzedzie('Młotek');

        $this->actingAs($this->biuro)
            ->get('/narzedzia')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Narzedzia/Index')
                ->where('grupy.0.photo', null)
                ->where('grupy.0.nazwa', 'Młotek')
                ->etc()
            );
    }

    public function test_lista_nie_robi_zapytania_na_kazdy_wiersz(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $nazwa) {
            $this->plik($this->narzedzie($nazwa), 'foto.jpg', 'photo');
        }

        $zapytania = 0;
        DB::listen(function () use (&$zapytania) {
            $zapytania++;
        });

        $this->actingAs($this->biuro)->get('/narzedzia')->assertOk();

        // Bez eager loadingu każdy wiersz dokładałby własne zapytanie o zdjęcia.
        $this->assertLessThan(15, $zapytania, "Za dużo zapytań: {$zapytania}");
    }

    private function narzedzie(string $nazwa, ?int $typId = null): Narzedzia
    {
        return Narzedzia::create([
            'name' => $nazwa,
            'narzedzia_typ_id' => $typId,
            'numer_seryjny' => 'SN-'.$nazwa,
            'waznosc_badan' => '2027-01-01',
            'ilosc_all' => 3,
            'ilosc_budowa' => 1,
        ]);
    }

    private function plik(Narzedzia $narzedzie, string $nazwa, string $typ): ToolFile
    {
        return ToolFile::create([
            'tool_id' => $narzedzie->id,
            'filename' => $nazwa,
            'type' => $typ,
        ]);
    }
}
