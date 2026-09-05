<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zakładanie typów sprzętu: z Ustawień i wprost z formularza sprzętu.
 * W obu miejscach da się od razu wskazać kategorię, żeby nowy model
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

    public function test_ustawienia_zakladaja_typ_z_kategoria(): void
    {
        $this->actingAs($this->admin)
            ->post('/narzedziaTyp', ['name' => 'Kontener 9m', 'kategoria' => 'Kontener'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $typ = NarzedziaTyp::firstWhere('name', 'Kontener 9m');

        $this->assertNotNull($typ, 'Typ nie powstał.');
        $this->assertSame('Kontener', $typ->kategoria);
    }

    public function test_ustawienia_zmieniaja_kategorie_istniejacego_typu(): void
    {
        $typ = NarzedziaTyp::create(['name' => 'Manitou MT1840']);

        $this->actingAs($this->admin)
            ->put('/narzedziaTyp/'.$typ->id, ['name' => 'Manitou MT1840', 'kategoria' => 'Manitou'])
            ->assertRedirect();

        $this->assertSame('Manitou', $typ->fresh()->kategoria);
    }

    public function test_nowy_typ_z_formularza_sprzetu_dostaje_kategorie(): void
    {
        $this->actingAs($this->admin)
            ->post('/narzedzia', [
                'new_typ_name' => 'Kontener 9m',
                'new_typ_kategoria' => 'Kontener',
                'numer_seryjny' => 'SN-9',
                'ilosc_all' => 1,
            ])
            ->assertRedirect();

        $typ = NarzedziaTyp::firstWhere('name', 'Kontener 9m');

        $this->assertNotNull($typ);
        $this->assertSame('Kontener', $typ->kategoria);
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

    public function test_istniejacemu_typowi_nie_nadpisujemy_kategorii(): void
    {
        NarzedziaTyp::create(['name' => 'Kontener 6m', 'kategoria' => 'Kontener']);

        $this->actingAs($this->admin)->post('/narzedzia', [
            'new_typ_name' => 'Kontener 6m',
            'new_typ_kategoria' => 'Coś innego',
            'numer_seryjny' => 'SN-10',
            'ilosc_all' => 1,
        ]);

        $this->assertSame('Kontener', NarzedziaTyp::firstWhere('name', 'Kontener 6m')->kategoria);
    }
}
