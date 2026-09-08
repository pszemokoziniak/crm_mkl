<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\GrupaSprzetu;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zakładanie typów sprzętu: z Ustawień i wprost z formularza sprzętu.
 * W obu miejscach da się od razu wskazać grupę, żeby nowy model
 * stanął w magazynie pod właściwą pozycją.
 */
class TypySprzetuTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'account_id' => Account::create(['name' => 'MKL'])->id,
            'email' => 'admin@mkl.pl',
            'owner' => 1,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_ustawienia_zakladaja_typ_z_grupa(): void
    {
        $this->actingAs($this->admin)
            ->post('/narzedziaTyp', ['name' => 'Kontener 9m', 'grupa' => 'Kontener'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $typ = NarzedziaTyp::firstWhere('name', 'Kontener 9m');

        $this->assertNotNull($typ, 'Typ nie powstał.');
        $this->assertSame('Kontener', $typ->nazwaGrupy());
    }

    public function test_ustawienia_zmieniaja_grupe_istniejacego_typu(): void
    {
        $typ = NarzedziaTyp::create(['name' => 'Manitou MT1840']);

        $this->actingAs($this->admin)
            ->put('/narzedziaTyp/'.$typ->id, ['name' => 'Manitou MT1840', 'grupa' => 'Manitou'])
            ->assertRedirect();

        $this->assertSame('Manitou', $typ->fresh()->nazwaGrupy());
    }

    public function test_nowy_typ_z_formularza_sprzetu_dostaje_grupe(): void
    {
        $this->actingAs($this->admin)
            ->post('/narzedzia', [
                'new_typ_name' => 'Kontener 9m',
                'new_typ_grupa' => 'Kontener',
                'numer_seryjny' => 'SN-9',
                'ilosc_all' => 1,
            ])
            ->assertRedirect();

        $typ = NarzedziaTyp::firstWhere('name', 'Kontener 9m');

        $this->assertNotNull($typ);
        $this->assertSame('Kontener', $typ->nazwaGrupy());
        $this->assertSame($typ->id, Narzedzia::firstWhere('numer_seryjny', 'SN-9')->narzedzia_typ_id);
    }

    public function test_sprzet_bez_daty_badan_da_sie_zapisac(): void
    {
        // Kolumna była NOT NULL i zapis kończył się komunikatem o plikach —
        // stąd w bazie daty-zastępniki z rokiem 9999.
        $this->actingAs($this->admin)
            ->post('/narzedzia', [
                'new_typ_name' => 'Kontener 9m',
                'numer_seryjny' => 'SN-BEZ-DATY',
                'ilosc_all' => 1,
            ])
            ->assertRedirect();

        $sprzet = Narzedzia::firstWhere('numer_seryjny', 'SN-BEZ-DATY');

        $this->assertNotNull($sprzet, 'Sprzęt bez daty badań nie został zapisany.');
        $this->assertNull($sprzet->waznosc_badan);
    }

    public function test_istniejacemu_typowi_nie_nadpisujemy_grupy(): void
    {
        NarzedziaTyp::create(['name' => 'Kontener 6m', 'grupa_id' => GrupaSprzetu::zNazwy('Kontener')->id]);

        $this->actingAs($this->admin)->post('/narzedzia', [
            'new_typ_name' => 'Kontener 6m',
            'new_typ_grupa' => 'Coś innego',
            'numer_seryjny' => 'SN-10',
            'ilosc_all' => 1,
        ]);

        $this->assertSame('Kontener', NarzedziaTyp::firstWhere('name', 'Kontener 6m')->nazwaGrupy());
    }

    public function test_nowa_grupa_powstaje_z_formularza_sprzetu(): void
    {
        // Na formularzu grupę można wpisać z ręki. Skoro grupa jest osobnym
        // rekordem, musi się przy okazji założyć — inaczej typ zostałby bez niej.
        $this->actingAs($this->admin)
            ->post('/narzedzia', [
                'new_typ_name' => 'Liebherr 81 K.1',
                'new_typ_grupa' => 'Żuraw',
                'numer_seryjny' => 'SN-Z1',
                'ilosc_all' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grupy_sprzetu', ['nazwa' => 'Żuraw']);
        $this->assertSame('Żuraw', NarzedziaTyp::firstWhere('name', 'Liebherr 81 K.1')->nazwaGrupy());
    }

    public function test_sprzet_dodany_z_formularza_jest_od_razu_dostepny(): void
    {
        // Formularz startował z ilością 0, przez co nowa sztuka miała 0 w
        // magazynie i przypisanie jej do budowy kończyło się komunikatem
        // "Brak wystarczającej ilości w magazynie".
        $this->actingAs($this->admin)
            ->post('/narzedzia', [
                'new_typ_name' => 'Manitou MT 1840',
                'numer_seryjny' => 'SN-DOSTEPNY',
                'ilosc_all' => 1,
            ])
            ->assertRedirect();

        $sprzet = Narzedzia::firstWhere('numer_seryjny', 'SN-DOSTEPNY');

        $this->assertNotNull($sprzet);
        $this->assertSame(1, $sprzet->ilosc_magazyn, 'Nowa sztuka ma być dostępna od razu.');
    }
}
