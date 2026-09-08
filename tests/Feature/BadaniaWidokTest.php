<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\DokumentyTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Ekran badań lekarskich: kolejność od najświeższego, stan terminu, kosz
 * dla wpisów i dla skanów. Usunięcie skanu było dotąd nieodwracalne.
 */
class BadaniaWidokTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Contact $pracownik;
    private int $typBadania;

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
            'account_id' => $accountId, 'first_name' => 'Marcin', 'last_name' => 'Bącik',
        ]);

        $this->typBadania = BadaniaTyp::create(['name' => 'Badanie okresowe'])->id;
    }

    private function badanie(string $koniec): Badania
    {
        return Badania::create([
            'contact_id' => $this->pracownik->id,
            'badaniaTyp_id' => $this->typBadania,
            'start' => now()->subYear()->toDateString(),
            'end' => $koniec,
        ]);
    }

    private function skan(string $nazwa = 'Skan badania'): CtnDocument
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
        $adres = "/contacts/{$this->pracownik->id}/badania".($filtry ? '?'.http_build_query($filtry) : '');

        return $this->actingAs($this->biuro)->get($adres)->viewData('page')['props'];
    }

    public function test_ekran_dostaje_wszystko_czego_uzywa_szablon(): void
    {
        $this->badanie(now()->addYear()->toDateString());
        $this->skan();

        $p = $this->props();

        // Szablon sięga po te pola wprost — brak któregokolwiek to pusty
        // nagłówek albo wywalona paginacja, czego testy logiki nie wychwycą.
        // Nagłówek dostaje obiekt {id, nazwa} — id napędza odnośnik do karty.
        $this->assertSame('Bącik Marcin', $p['pracownik']['nazwa']);
        $this->assertSame($this->pracownik->id, $p['pracownik']['id']);
        $this->assertArrayHasKey('links', json_decode(json_encode($p['bads']), true));
        $this->assertArrayHasKey('data', json_decode(json_encode($p['documents']), true));

        $wiersz = collect($p['bads']['data'])->first();
        foreach (['id', 'start', 'end', 'name', 'deleted_at', 'dni'] as $pole) {
            $this->assertArrayHasKey($pole, $wiersz);
        }
    }

    public function test_najswiezsze_badanie_na_gorze(): void
    {
        $this->badanie(now()->subYear()->toDateString());
        $this->badanie(now()->addYear()->toDateString());
        $this->badanie(now()->addMonth()->toDateString());

        $konce = collect($this->props()['bads']['data'])->pluck('end')->all();

        $this->assertSame([
            now()->addYear()->toDateString(),
            now()->addMonth()->toDateString(),
            now()->subYear()->toDateString(),
        ], $konce);
    }

    public function test_ekran_niesie_stan_terminu(): void
    {
        $this->badanie(now()->addDays(10)->toDateString());
        $this->badanie(now()->subDays(3)->toDateString());

        $dni = collect($this->props()['bads']['data'])->pluck('dni')->all();

        // Dodatnie = ile zostało, ujemne = ile po terminie.
        $this->assertSame([10, -3], $dni);
    }

    public function test_badanie_idzie_do_kosza_i_wraca(): void
    {
        $b = $this->badanie(now()->addYear()->toDateString());

        $this->actingAs($this->biuro)->delete("/badania/{$b->id}")->assertRedirect();
        $this->assertSoftDeleted('badanias', ['id' => $b->id]);

        // Domyślnie kosza nie widać, po zaznaczeniu — widać.
        $this->assertCount(0, $this->props()['bads']['data']);
        $wKoszu = collect($this->props(['trashed' => 'with'])['bads']['data']);
        $this->assertCount(1, $wKoszu);
        $this->assertNotNull($wKoszu->first()['deleted_at']);

        // Przywracanie dotąd dawało 404 — wiązanie trasy pomijało kosz.
        $this->actingAs($this->biuro)->put("/badania/{$b->id}/restore")->assertRedirect();
        $this->assertDatabaseHas('badanias', ['id' => $b->id, 'deleted_at' => null]);
    }

    public function test_skan_idzie_do_kosza_zamiast_znikac_na_zawsze(): void
    {
        $d = $this->skan();

        $this->actingAs($this->biuro)
            ->delete("/contacts/{$this->pracownik->id}/documents/{$d->id}/lekarskie")
            ->assertRedirect();

        $this->assertSoftDeleted('ctn_documents', ['id' => $d->id]);
        // Plik zostaje na dysku — bez niego przywrócenie dałoby pusty wiersz.
        $this->assertTrue(Storage::disk('local')->exists($d->path));
    }

    public function test_skan_z_kosza_wraca_i_znowu_da_sie_pobrac(): void
    {
        $d = $this->skan();
        $adres = "/contacts/{$this->pracownik->id}/documents/{$d->id}";

        $this->actingAs($this->biuro)->delete($adres.'/lekarskie');
        $this->actingAs($this->biuro)->get($adres)->assertNotFound();

        $this->actingAs($this->biuro)->put($adres.'/restore')->assertRedirect();
        $this->assertDatabaseHas('ctn_documents', ['id' => $d->id, 'deleted_at' => null]);
        $this->actingAs($this->biuro)->get($adres)->assertOk();
    }

    public function test_usuniety_skan_znika_z_listy_dopoki_nie_pokazemy_kosza(): void
    {
        $d = $this->skan();
        $this->actingAs($this->biuro)->delete("/contacts/{$this->pracownik->id}/documents/{$d->id}/lekarskie");

        $this->assertCount(0, $this->props()['documents']['data']);
        $this->assertCount(1, $this->props(['trashed' => 'with'])['documents']['data']);
    }

    public function test_nie_skasujemy_dokumentu_podstawiajac_cudzego_pracownika(): void
    {
        $d = $this->skan();
        $inny = Contact::create(['account_id' => 1, 'first_name' => 'Ktos', 'last_name' => 'Inny']);

        $this->actingAs($this->biuro)
            ->delete("/contacts/{$inny->id}/documents/{$d->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('ctn_documents', ['id' => $d->id, 'deleted_at' => null]);
    }

    public function test_kierownik_nie_dostaje_przyciskow_kosza(): void
    {
        $b = $this->badanie(now()->addYear()->toDateString());

        $kierownik = User::factory()->create([
            'account_id' => $this->biuro->account_id, 'email' => 'kier@mkl.pl',
            'owner' => Role::KIEROWNIK->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($kierownik)->delete("/badania/{$b->id}")->assertForbidden();
        $this->assertDatabaseHas('badanias', ['id' => $b->id, 'deleted_at' => null]);
    }
}
