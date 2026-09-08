<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\DokumentyTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Zakładka Dokumenty: nagłówek z nazwiskiem (dotąd był sam tytuł "Dokumenty",
 * bez śladu, czyją kartę się ogląda) i kosz jak w pozostałych sekcjach.
 */
class EkranDokumentowTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Contact $pracownik;

    protected function setUp(): void
    {
        parent::setUp();

        $accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $accountId, 'email' => 'biuro@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->pracownik = Contact::create([
            'account_id' => $accountId, 'first_name' => 'Robert', 'last_name' => 'Biskupiak',
        ]);
    }

    private function skan(string $nazwa = 'badanie okresowe'): CtnDocument
    {
        $sciezka = 'dokumenty/'.uniqid().'.pdf';
        Storage::disk('local')->put($sciezka, 'tresc');
        $typ = DokumentyTyp::firstOrCreate(['id' => 1], ['name' => 'Badania Lekarskie']);

        $d = CtnDocument::create($nazwa, $typ->id, $sciezka, $this->pracownik->id, 'skan.pdf');
        $d->save();

        return $d;
    }

    private function props(array $filtry = []): array
    {
        $adres = "/contacts/{$this->pracownik->id}/documents/".($filtry ? '?'.http_build_query($filtry) : '');

        return $this->actingAs($this->biuro)->get($adres)->viewData('page')['props'];
    }

    public function test_naglowek_mowi_czyja_karte_ogladamy(): void
    {
        $this->skan();

        $p = $this->props()['pracownik'];

        $this->assertSame('Biskupiak Robert', $p['nazwa']);
        $this->assertSame($this->pracownik->id, $p['id']);
    }

    public function test_lista_niesie_typ_dokumentu(): void
    {
        $this->skan();

        $wiersz = collect($this->props()['documents']['data'])->first();

        $this->assertSame('Badania Lekarskie', $wiersz['dokumentytyp']['name']);
    }

    public function test_kosz_dziala_takze_z_tego_ekranu(): void
    {
        $d = $this->skan();
        $adres = "/contacts/{$this->pracownik->id}/documents/{$d->id}";

        // Trasa podstawowa, bez wariantu sekcji — stąd wraca się na Dokumenty.
        $this->actingAs($this->biuro)->delete($adres)->assertRedirect();
        $this->assertSoftDeleted('ctn_documents', ['id' => $d->id]);
        $this->assertTrue(Storage::disk('local')->exists($d->path));

        $this->assertCount(0, $this->props()['documents']['data']);
        $this->assertCount(1, $this->props(['trashed' => 'with'])['documents']['data']);

        $this->actingAs($this->biuro)->put($adres.'/restore')->assertRedirect();
        $this->assertDatabaseHas('ctn_documents', ['id' => $d->id, 'deleted_at' => null]);
    }

    public function test_kierownik_widzi_ekran_ale_bez_dodawania(): void
    {
        $this->skan();

        $kierownik = User::factory()->create([
            'account_id' => $this->biuro->account_id, 'email' => 'kier@mkl.pl',
            'owner' => Role::KIEROWNIK->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        // Bez przypisania do budowy kierownik nie zobaczy cudzego pracownika.
        $this->actingAs($kierownik)
            ->get("/contacts/{$this->pracownik->id}/documents/")
            ->assertForbidden();
    }
}
