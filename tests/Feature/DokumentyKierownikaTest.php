<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\CtnDocument;
use App\Models\DokumentyTyp;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kierownik budowy ogląda i pobiera dokumenty SWOICH pracowników — potrzebuje
 * ich przy kontroli i przy wejściu inwestora na budowę. Dodawać i kasować
 * dalej może tylko biuro, a dokumentów obcych pracowników nie zobaczy wcale.
 */
class DokumentyKierownikaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $kierownik;
    private Contact $mojPracownik;
    private Contact $obcyPracownik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $funkcja = Funkcja::create([
            'id' => Funkcja::KIEROWNIK,
            'name' => 'Kierownik Budowy',
            'kierownictwo' => true,
            'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
        ]);

        $this->kierownik = User::factory()->create([
            'account_id' => $this->accountId,
            'first_name' => 'Adam', 'last_name' => 'Kierowniczak',
            'email' => 'kierownik@mkl.pl', 'owner' => Role::KIEROWNIK->value,
            'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $kontaktKierownika = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Adam', 'last_name' => 'Kierowniczak',
            'funkcja_id' => $funkcja->id, 'user_id' => $this->kierownik->id,
        ]);

        $moja = Organization::create(['account_id' => 0, 'name' => 'V', 'nazwaBud' => 'Moja budowa']);
        $cudza = Organization::create(['account_id' => 0, 'name' => 'A', 'nazwaBud' => 'Cudza budowa']);

        $this->naBudowie($kontaktKierownika, $moja);
        $this->mojPracownik = $this->pracownik('Mojski', $moja);
        $this->obcyPracownik = $this->pracownik('Obcy', $cudza);
    }

    private function naBudowie(Contact $c, Organization $o): void
    {
        ContactWorkDate::create([
            'contact_id' => $c->id, 'organization_id' => $o->id,
            'start' => now()->subMonth()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);
    }

    private function pracownik(string $nazwisko, Organization $budowa): Contact
    {
        $c = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan', 'last_name' => $nazwisko,
        ]);
        $this->naBudowie($c, $budowa);

        return $c;
    }

    /** CtnDocument::create ma własną sygnaturę i nie zapisuje — stąd ->save(). */
    private function dokument(Contact $c, string $nazwa = 'Skan dowodu'): CtnDocument
    {
        $sciezka = "dokumenty/{$c->id}-".uniqid().'.pdf';
        Storage::disk('local')->put($sciezka, 'tresc-testowa');

        $typ = DokumentyTyp::firstOrCreate(['name' => 'Dowód osobisty']);

        $d = CtnDocument::create($nazwa, $typ->id, $sciezka, $c->id, 'skan.pdf');
        $d->save();

        return $d;
    }

    private function biuro(): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_kierownik_oglada_liste_dokumentow_swojego_pracownika(): void
    {
        $this->dokument($this->mojPracownik);

        $this->actingAs($this->kierownik)
            ->get("/contacts/{$this->mojPracownik->id}/documents/")
            ->assertOk();
    }

    public function test_kierownik_pobiera_dokument_swojego_pracownika(): void
    {
        $d = $this->dokument($this->mojPracownik);

        $this->actingAs($this->kierownik)
            ->get("/contacts/{$this->mojPracownik->id}/documents/{$d->id}")
            ->assertOk()
            ->assertDownload('skan.pdf');
    }

    public function test_kierownik_nie_siegnie_po_dokumenty_obcego_pracownika(): void
    {
        $d = $this->dokument($this->obcyPracownik);

        $this->actingAs($this->kierownik)
            ->get("/contacts/{$this->obcyPracownik->id}/documents/")
            ->assertForbidden();

        $this->actingAs($this->kierownik)
            ->get("/contacts/{$this->obcyPracownik->id}/documents/{$d->id}")
            ->assertForbidden();
    }

    public function test_nie_podmieni_id_pracownika_zeby_pobrac_cudzy_dokument(): void
    {
        $cudzy = $this->dokument($this->obcyPracownik);

        // Własny pracownik w adresie (więc middleware przepuszcza),
        // ale id dokumentu należącego do kogoś innego.
        $this->actingAs($this->kierownik)
            ->get("/contacts/{$this->mojPracownik->id}/documents/{$cudzy->id}")
            ->assertNotFound();
    }

    public function test_kierownik_nie_dodaje_i_nie_kasuje(): void
    {
        $d = $this->dokument($this->mojPracownik);

        $this->actingAs($this->kierownik)
            ->get("/contacts/{$this->mojPracownik->id}/documents/create")
            ->assertForbidden();

        $this->actingAs($this->kierownik)
            ->delete("/contacts/{$this->mojPracownik->id}/documents/{$d->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('ctn_documents', ['id' => $d->id]);
    }

    public function test_biuro_dziala_jak_dotad(): void
    {
        $biuro = $this->biuro();
        $d = $this->dokument($this->obcyPracownik);

        $this->actingAs($biuro)->get("/contacts/{$this->obcyPracownik->id}/documents/")->assertOk();
        $this->actingAs($biuro)->get("/contacts/{$this->obcyPracownik->id}/documents/{$d->id}")->assertOk();
        $this->actingAs($biuro)->get("/contacts/{$this->obcyPracownik->id}/documents/create")->assertOk();
    }

    public function test_brakujacy_plik_na_dysku_daje_404_a_nie_wyjatek(): void
    {
        $typ = DokumentyTyp::firstOrCreate(['name' => 'Dowód osobisty']);
        $d = CtnDocument::create('Bez pliku', $typ->id, 'dokumenty/nie-ma-takiego.pdf', $this->mojPracownik->id, 'nic.pdf');
        $d->save();

        $this->actingAs($this->biuro())
            ->get("/contacts/{$this->mojPracownik->id}/documents/{$d->id}")
            ->assertNotFound();
    }
}
