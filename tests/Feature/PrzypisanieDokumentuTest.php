<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TypDokumentu;
use App\Models\Account;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Models\Bhp;
use App\Models\BhpTyp;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\DokumentyTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Formularz "Dodaj dokument" pozwala teraz wskazać wpis, którego dokument
 * dotyczy. Dotąd dało się go dodać tylko luzem — z typem, ale bez związku
 * z konkretnym badaniem czy szkoleniem.
 */
class PrzypisanieDokumentuTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Contact $pracownik;
    private Contact $inny;

    protected function setUp(): void
    {
        parent::setUp();

        $accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $accountId, 'email' => 'biuro@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->pracownik = Contact::create(['account_id' => $accountId, 'first_name' => 'Marcin', 'last_name' => 'Bącik']);
        $this->inny = Contact::create(['account_id' => $accountId, 'first_name' => 'Jan', 'last_name' => 'Obcy']);

        foreach (TypDokumentu::cases() as $t) {
            DokumentyTyp::firstOrCreate(['id' => $t->value], ['name' => $t->label()]);
        }
    }

    private function badanie(Contact $c, string $koniec = '2028-06-02'): Badania
    {
        return Badania::create([
            'contact_id' => $c->id,
            'badaniaTyp_id' => BadaniaTyp::firstOrCreate(['name' => 'Okresowe'])->id,
            'start' => '2026-06-02', 'end' => $koniec,
        ]);
    }

    private function wyslij(array $dane): \Illuminate\Testing\TestResponse
    {
        Storage::fake('local');

        return $this->actingAs($this->biuro)->post("/contacts/{$this->pracownik->id}/documents/store", $dane + [
            'name' => 'Skan badania',
            'documents' => [UploadedFile::fake()->create('skan.pdf', 40, 'application/pdf')],
        ]);
    }

    public function test_formularz_podaje_wpisy_do_wyboru(): void
    {
        $badanie = $this->badanie($this->pracownik);
        Bhp::create([
            'contact_id' => $this->pracownik->id,
            'bhpTyp_id' => BhpTyp::create(['name' => 'Okresowe BHP'])->id,
            'start' => '2026-01-01', 'end' => '2027-01-01',
        ]);

        $wpisy = $this->actingAs($this->biuro)
            ->get("/contacts/{$this->pracownik->id}/documents/create")
            ->viewData('page')['props']['wpisy'];

        $this->assertCount(1, $wpisy[TypDokumentu::BADANIA->value]);
        $this->assertSame($badanie->id, $wpisy[TypDokumentu::BADANIA->value][0]['id']);
        // Opis ma pozwolić odróżnić dwa badania tego samego rodzaju.
        $this->assertSame('Okresowe · 2026-06-02 → 2028-06-02', $wpisy[TypDokumentu::BADANIA->value][0]['etykieta']);
        $this->assertCount(1, $wpisy[TypDokumentu::BHP->value]);
        $this->assertSame([], $wpisy[TypDokumentu::A1->value]);
    }

    public function test_dokument_przypisuje_sie_do_wskazanego_wpisu(): void
    {
        $badanie = $this->badanie($this->pracownik);

        $this->wyslij([
            'typ' => TypDokumentu::BADANIA->value,
            'zrodlo_id' => $badanie->id,
        ])->assertRedirect();

        $dokument = CtnDocument::first();
        $this->assertSame($badanie->id, (int) $dokument->zrodlo_id);
        $this->assertSame(Badania::class, $dokument->zrodlo_type);
    }

    public function test_bez_wskazania_dokument_zostaje_luzny(): void
    {
        $this->badanie($this->pracownik);

        $this->wyslij(['typ' => TypDokumentu::BADANIA->value])->assertRedirect();

        $this->assertNull(CtnDocument::first()->zrodlo_id);
    }

    public function test_nie_przypnie_dokumentu_do_wpisu_innego_pracownika(): void
    {
        $cudze = $this->badanie($this->inny);

        $this->wyslij([
            'typ' => TypDokumentu::BADANIA->value,
            'zrodlo_id' => $cudze->id,
        ])->assertRedirect();

        // Dokument powstaje, ale bez powiązania — nie wolno podpiąć go
        // pod badanie kogoś innego przez podmianę id w formularzu.
        $this->assertNull(CtnDocument::first()->zrodlo_id);
    }

    public function test_nie_przypnie_wpisu_z_innej_sekcji(): void
    {
        $bhp = Bhp::create([
            'contact_id' => $this->pracownik->id,
            'bhpTyp_id' => BhpTyp::create(['name' => 'Okresowe BHP'])->id,
            'start' => '2026-01-01', 'end' => '2027-01-01',
        ]);

        // Typ mówi "badania", a id wskazuje na szkolenie BHP.
        $this->wyslij([
            'typ' => TypDokumentu::BADANIA->value,
            'zrodlo_id' => $bhp->id,
        ])->assertRedirect();

        $this->assertNull(CtnDocument::first()->zrodlo_id);
    }

    public function test_przypisany_dokument_widac_przy_wpisie(): void
    {
        $badanie = $this->badanie($this->pracownik);

        $this->wyslij(['typ' => TypDokumentu::BADANIA->value, 'zrodlo_id' => $badanie->id]);

        $wiersz = collect(
            $this->actingAs($this->biuro)
                ->get("/contacts/{$this->pracownik->id}/badania")
                ->viewData('page')['props']['bads']['data']
        )->firstWhere('id', $badanie->id);

        $this->assertSame(CtnDocument::first()->id, $wiersz['skan']);
    }
}
