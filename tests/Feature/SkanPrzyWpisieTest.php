<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TypDokumentu;
use App\Models\Account;
use App\Models\BadaniaTyp;
use App\Models\BhpTyp;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\DokumentyTyp;
use App\Models\UprawnieniaTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Skan wgrywany od razu przy tworzeniu wpisu. Dotąd dokument dodawało się
 * osobno w zakładce Dokumenty i nic nie łączyło go z konkretnym badaniem
 * czy szkoleniem — było wiadomo tylko, że należy do pracownika.
 */
class SkanPrzyWpisieTest extends TestCase
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

        foreach (TypDokumentu::cases() as $typ) {
            DokumentyTyp::firstOrCreate(['id' => $typ->value], ['name' => $typ->label()]);
        }
    }

    /** @return array<string, array{string, string, array<string, mixed>, int}> */
    public function sekcje(): array
    {
        return [
            'badania' => ['badania', 'badanias', ['badaniaTyp_id' => 'BADANIA_TYP'], TypDokumentu::BADANIA->value],
            'bhp' => ['bhp', 'bhps', ['bhpTyp_id' => 'BHP_TYP'], TypDokumentu::BHP->value],
            'uprawnienia' => ['uprawnienia', 'uprawnienias', ['uprawnieniaTyp_id' => 'UPR_TYP'], TypDokumentu::UPRAWNIENIA->value],
            'a1' => ['a1', 'a1_s', [], TypDokumentu::A1->value],
            'pbioz' => ['pbioz', 'pbiozs', ['name' => 'Plan BIOZ'], TypDokumentu::PBIOZ->value],
        ];
    }

    /** Podstawia prawdziwe id słowników w miejsce znaczników. */
    private function dane(array $pola): array
    {
        $mapa = [
            'BADANIA_TYP' => fn () => BadaniaTyp::create(['name' => 'Okresowe'])->id,
            'BHP_TYP' => fn () => BhpTyp::create(['name' => 'Okresowe'])->id,
            'UPR_TYP' => fn () => UprawnieniaTyp::create(['name' => 'Koparka'])->id,
        ];

        foreach ($pola as $klucz => $wartosc) {
            if (isset($mapa[$wartosc])) {
                $pola[$klucz] = $mapa[$wartosc]();
            }
        }

        return $pola + [
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addYear()->toDateString(),
        ];
    }

    /** @dataProvider sekcje */
    public function test_skan_wgrany_przy_wpisie_ladu_je_w_dokumentach(string $trasa, string $tabela, array $pola, int $typId): void
    {
        Storage::fake('local');

        $this->actingAs($this->biuro)
            ->post("/{$trasa}/{$this->pracownik->id}", $this->dane($pola) + [
                'skan' => UploadedFile::fake()->create('badanie.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount($tabela, 1);

        $dokument = CtnDocument::first();
        $this->assertNotNull($dokument, "$trasa: skan miał zostać zapisany");
        $this->assertSame($this->pracownik->id, (int) $dokument->contact_id);
        $this->assertSame((string) $typId, (string) $dokument->dokumentytyp_id, "$trasa: zły typ dokumentu");
        $this->assertSame('badanie.pdf', $dokument->filename);
    }

    /** @dataProvider sekcje */
    public function test_dokument_jest_powiazany_z_konkretnym_wpisem(string $trasa, string $tabela, array $pola): void
    {
        Storage::fake('local');

        $this->actingAs($this->biuro)->post("/{$trasa}/{$this->pracownik->id}", $this->dane($pola) + [
            'skan' => UploadedFile::fake()->create('skan.pdf', 50, 'application/pdf'),
        ]);

        $dokument = CtnDocument::first();
        $wpisId = \DB::table($tabela)->value('id');

        // O to chodziło w zgłoszeniu: wiadomo, którego wpisu dotyczy skan.
        $this->assertSame((int) $wpisId, (int) $dokument->zrodlo_id, "$trasa: brak powiązania");
        $this->assertNotNull($dokument->zrodlo, "$trasa: relacja nie rozwiązuje się na model");
    }

    /** @dataProvider sekcje */
    public function test_wpis_bez_skanu_dziala_jak_dotad(string $trasa, string $tabela, array $pola): void
    {
        Storage::fake('local');

        $this->actingAs($this->biuro)
            ->post("/{$trasa}/{$this->pracownik->id}", $this->dane($pola))
            ->assertRedirect();

        $this->assertDatabaseCount($tabela, 1);
        $this->assertSame(0, CtnDocument::count(), "$trasa: bez pliku nie powinno powstać nic w dokumentach");
    }

    public function test_za_duzy_plik_jest_odrzucany(): void
    {
        Storage::fake('local');

        $this->actingAs($this->biuro)
            ->post("/badania/{$this->pracownik->id}", $this->dane(['badaniaTyp_id' => 'BADANIA_TYP']) + [
                'skan' => UploadedFile::fake()->create('wielki.pdf', 25000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('skan');

        $this->assertSame(0, CtnDocument::count());
    }

    public function test_skan_widac_przy_wpisie_na_liscie(): void
    {
        Storage::fake('local');

        $this->actingAs($this->biuro)->post("/badania/{$this->pracownik->id}", $this->dane(['badaniaTyp_id' => 'BADANIA_TYP']) + [
            'skan' => UploadedFile::fake()->create('skan.pdf', 40, 'application/pdf'),
        ]);

        $wiersz = collect(
            $this->actingAs($this->biuro)
                ->get("/contacts/{$this->pracownik->id}/badania")
                ->viewData('page')['props']['bads']['data']
        )->first();

        $this->assertSame(CtnDocument::first()->id, $wiersz['skan']);
    }
}
