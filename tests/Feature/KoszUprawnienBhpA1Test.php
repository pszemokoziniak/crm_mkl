<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\A1;
use App\Models\Account;
use App\Models\Bhp;
use App\Models\BhpTyp;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\DokumentyTyp;
use App\Models\Uprawnienia;
use App\Models\UprawnieniaTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uprawnienia, BHP i A1 — to samo co przy badaniach: kolejność od
 * najświeższego, stan terminu i kosz zamiast kasowania na zawsze.
 *
 * Tabele bhps i uprawnienias miały deleted_at od dawna, ale modele nie
 * używały SoftDeletes — kolumna leżała bezużytecznie, a usunięcie było
 * nieodwracalne. a1_s nie miało jej wcale.
 */
class KoszUprawnienBhpA1Test extends TestCase
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
            'account_id' => $accountId, 'first_name' => 'Marcin', 'last_name' => 'Bącik',
        ]);
    }

    private function props(string $sekcja, array $filtry = []): array
    {
        $adres = "/contacts/{$this->pracownik->id}/{$sekcja}".($filtry ? '?'.http_build_query($filtry) : '');

        return $this->actingAs($this->biuro)->get($adres)->viewData('page')['props'];
    }

    private function skan(int $typId, string $nazwaTypu): CtnDocument
    {
        $sciezka = 'dokumenty/'.uniqid().'.pdf';
        Storage::disk('local')->put($sciezka, 'tresc');
        $typ = DokumentyTyp::firstOrCreate(['id' => $typId], ['name' => $nazwaTypu]);

        $d = CtnDocument::create('Skan', $typ->id, $sciezka, $this->pracownik->id, 'skan.pdf');
        $d->save();

        return $d;
    }

    /** @return array{0: object, 1: string, 2: string, 3: string} */
    private function przypadek(string $rodzaj): array
    {
        switch ($rodzaj) {
            case 'uprawnienia':
                $typ = UprawnieniaTyp::create(['name' => 'Operator koparki']);
                $m = Uprawnienia::create([
                    'contact_id' => $this->pracownik->id, 'uprawnieniaTyp_id' => $typ->id,
                    'start' => now()->subYear()->toDateString(), 'end' => now()->addDays(10)->toDateString(),
                ]);

                return [$m, 'uprawnienias', 'uprawnienia', 'uprawnienia'];
            case 'bhp':
                $typ = BhpTyp::create(['name' => 'Szkolenie okresowe']);
                $m = Bhp::create([
                    'contact_id' => $this->pracownik->id, 'bhpTyp_id' => $typ->id,
                    'start' => now()->subYear()->toDateString(), 'end' => now()->addDays(10)->toDateString(),
                ]);

                return [$m, 'bhps', 'bhp', 'bhp'];
            default:
                $m = A1::create([
                    'contact_id' => $this->pracownik->id,
                    'start' => now()->subYear()->toDateString(), 'end' => now()->addDays(10)->toDateString(),
                ]);

                return [$m, 'a1_s', 'a1', 'a1'];
        }
    }

    /** @dataProvider rodzaje */
    public function test_ekran_niesie_stan_terminu_i_nazwisko(string $rodzaj, string $propLista): void
    {
        [$m] = $this->przypadek($rodzaj);

        $p = $this->props($rodzaj);

        $this->assertSame('Bącik Marcin', $p['pracownik']);
        $wiersz = collect($p[$propLista]['data'])->firstWhere('id', $m->id);
        $this->assertSame(10, $wiersz['dni']);
        $this->assertNull($wiersz['deleted_at']);
    }

    /** @dataProvider rodzaje */
    public function test_wpis_idzie_do_kosza_i_wraca(string $rodzaj, string $propLista, string $tabela, string $trasa): void
    {
        [$m] = $this->przypadek($rodzaj);

        $this->actingAs($this->biuro)->delete("/{$trasa}/{$m->id}")->assertRedirect();
        $this->assertSoftDeleted($tabela, ['id' => $m->id]);

        $this->assertCount(0, $this->props($rodzaj)[$propLista]['data']);
        $this->assertCount(1, $this->props($rodzaj, ['trashed' => 'with'])[$propLista]['data']);

        // Przywracanie dotąd nie działało w żadnym z trzech: modele nie miały
        // SoftDeletes, więc restore() w ogóle nie istniało.
        $this->actingAs($this->biuro)->put("/{$trasa}/{$m->id}/restore")->assertRedirect();
        $this->assertDatabaseHas($tabela, ['id' => $m->id, 'deleted_at' => null]);
    }

    /** @dataProvider rodzaje */
    public function test_najswiezszy_wpis_na_gorze(string $rodzaj, string $propLista): void
    {
        [$blizszy] = $this->przypadek($rodzaj);
        $dalszy = $blizszy->replicate();
        $dalszy->end = now()->addYears(3)->toDateString();
        $dalszy->save();

        $konce = collect($this->props($rodzaj)[$propLista]['data'])->pluck('end')->all();

        $this->assertSame(now()->addYears(3)->toDateString(), $konce[0]);
    }

    /** @return array<string, array{string, string, string, string}> */
    public function rodzaje(): array
    {
        return [
            'uprawnienia' => ['uprawnienia', 'uprawnienias', 'uprawnienias', 'uprawnienia'],
            'bhp' => ['bhp', 'bhps', 'bhps', 'bhp'],
            'a1' => ['a1', 'a1s', 'a1_s', 'a1'],
        ];
    }

    public function test_skany_kazdej_sekcji_takze_ida_do_kosza(): void
    {
        foreach ([['uprawnienia', 3, 'Uprawnienia'], ['bhp', 2, 'Szkolenia BHP'], ['a1', 4, 'A1']] as [$sekcja, $typId, $nazwa]) {
            $d = $this->skan($typId, $nazwa);

            $this->actingAs($this->biuro)
                ->delete("/contacts/{$this->pracownik->id}/documents/{$d->id}/{$sekcja}")
                ->assertRedirect();

            $this->assertSoftDeleted('ctn_documents', ['id' => $d->id]);
            $this->assertTrue(Storage::disk('local')->exists($d->path), "$sekcja: plik ma zostać na dysku");

            $this->actingAs($this->biuro)
                ->put("/contacts/{$this->pracownik->id}/documents/{$d->id}/restore")
                ->assertRedirect();
            $this->assertDatabaseHas('ctn_documents', ['id' => $d->id, 'deleted_at' => null]);
        }
    }
}
