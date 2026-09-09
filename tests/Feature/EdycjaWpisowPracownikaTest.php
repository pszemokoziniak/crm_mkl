<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\A1;
use App\Models\Account;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Models\Bhp;
use App\Models\BhpTyp;
use App\Models\Contact;
use App\Models\KrajTyp;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
use App\Models\UprawnieniaTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Poprawianie dat we wpisach pracownika.
 *
 * Zgłoszenie: po zapisaniu błędnej daty nie da się jej poprawić, można tylko
 * usunąć wpis i założyć nowy. Zapis działał — na liście nie było widać, że
 * edycja w ogóle istnieje: jedynym wejściem była nazwa wpisu, renderowana
 * bez żadnego wyróżnienia.
 *
 * Przy okazji trasy wiążą wpis z pracownikiem z adresu. Wcześniej nie wiązały
 * niczego, a A1 brało wpis po identyfikatorze z ciała żądania.
 */
class EdycjaWpisowPracownikaTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Contact $pracownik;
    private Contact $obcy;

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
        $this->obcy = Contact::create([
            'account_id' => $accountId, 'first_name' => 'Halina', 'last_name' => 'Zub',
        ]);
    }

    /**
     * Wpis danego rodzaju wraz z adresem, tabelą i poprawnym ładunkiem PUT.
     *
     * @return array{0: object, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function przypadek(string $rodzaj, ?Contact $czyj = null): array
    {
        $czyj ??= $this->pracownik;
        $start = '2026-01-10';
        $zleEnd = '2026-02-20';   // data wpisana omyłkowo
        $dobreEnd = '2027-03-31'; // poprawka

        switch ($rodzaj) {
            case 'bhp':
                $typ = BhpTyp::create(['name' => 'Szkolenie okresowe']);
                $m = Bhp::create(['contact_id' => $czyj->id, 'bhpTyp_id' => $typ->id, 'start' => $start, 'end' => $zleEnd]);

                return [$m, 'bhp', 'bhps', ['bhpTyp_id' => $typ->id, 'start' => $start, 'end' => $dobreEnd]];
            case 'badania':
                $typ = BadaniaTyp::create(['name' => 'Okresowe']);
                $m = Badania::create(['contact_id' => $czyj->id, 'badaniaTyp_id' => $typ->id, 'start' => $start, 'end' => $zleEnd]);

                return [$m, 'badania', 'badanias', ['badaniaTyp_id' => $typ->id, 'start' => $start, 'end' => $dobreEnd]];
            case 'uprawnienia':
                $typ = UprawnieniaTyp::create(['name' => 'Operator koparki']);
                $m = Uprawnienia::create(['contact_id' => $czyj->id, 'uprawnieniaTyp_id' => $typ->id, 'start' => $start, 'end' => $zleEnd]);

                return [$m, 'uprawnienia', 'uprawnienias', ['uprawnieniaTyp_id' => $typ->id, 'start' => $start, 'end' => $dobreEnd]];
            case 'pbioz':
                $m = Pbioz::create(['contact_id' => $czyj->id, 'name' => 'Plan BIOZ', 'start' => $start, 'end' => $zleEnd]);

                return [$m, 'pbioz', 'pbiozs', ['name' => 'Plan BIOZ', 'start' => $start, 'end' => $dobreEnd]];
            default:
                $kraj = KrajTyp::create(['name' => 'Niemcy']);
                $m = A1::create(['contact_id' => $czyj->id, 'start' => $start, 'end' => $zleEnd]);

                return [$m, 'a1', 'a1_s', ['kraj_typs_id' => $kraj->id, 'start' => $start, 'end' => $dobreEnd]];
        }
    }

    /** @return array<string, array{0: string}> */
    public function rodzaje(): array
    {
        return [
            'BHP' => ['bhp'],
            'badania lekarskie' => ['badania'],
            'uprawnienia' => ['uprawnienia'],
            'PBiOZ' => ['pbioz'],
            'A1' => ['a1'],
        ];
    }

    /** @dataProvider rodzaje */
    public function test_ekran_edycji_sie_otwiera(string $rodzaj): void
    {
        [$m, $trasa] = $this->przypadek($rodzaj);

        $this->actingAs($this->biuro)
            ->get("/contacts/{$this->pracownik->id}/{$rodzaj}/{$m->id}/edit")
            ->assertOk();
    }

    /** @dataProvider rodzaje */
    public function test_blednie_wpisana_data_da_sie_poprawic(string $rodzaj): void
    {
        [$m, $trasa, $tabela, $ladunek] = $this->przypadek($rodzaj);

        $this->actingAs($this->biuro)
            ->put("/contacts/{$this->pracownik->id}/{$trasa}/{$m->id}", $ladunek)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas($tabela, ['id' => $m->id, 'end' => '2027-03-31']);
    }

    /** @dataProvider rodzaje */
    public function test_nie_poprawimy_wpisu_spod_cudzej_karty(string $rodzaj): void
    {
        // Wpis należy do obcego pracownika, adres wskazuje naszego.
        [$m, $trasa, $tabela, $ladunek] = $this->przypadek($rodzaj, $this->obcy);

        $this->actingAs($this->biuro)
            ->put("/contacts/{$this->pracownik->id}/{$trasa}/{$m->id}", $ladunek)
            ->assertNotFound();

        $this->assertDatabaseHas($tabela, ['id' => $m->id, 'end' => '2026-02-20']);
    }

    public function test_a1_nie_slucha_identyfikatora_z_ciala_zadania(): void
    {
        // A1 brało wpis przez A1::find($req->id), zupełnie pomijając adres —
        // podmiana tego pola pozwalała poprawić wpis obcego pracownika.
        [$moj, , , $ladunek] = $this->przypadek('a1');
        [$cudzy] = $this->przypadek('a1', $this->obcy);

        $this->actingAs($this->biuro)
            ->put("/contacts/{$this->pracownik->id}/a1/{$moj->id}", $ladunek + ['id' => $cudzy->id])
            ->assertRedirect();

        $this->assertDatabaseHas('a1_s', ['id' => $moj->id, 'end' => '2027-03-31']);
        $this->assertDatabaseHas('a1_s', ['id' => $cudzy->id, 'end' => '2026-02-20']);
    }
}
