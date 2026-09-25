<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\Organization;
use App\Models\ToolWorkDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprzęt przypisany do budowy bez daty końca: da się ustawić datę „do"
 * z zakładki edycji, bez zdejmowania sprzętu z budowy.
 */
class DataKoncaSprzetuTest extends TestCase
{
    use RefreshDatabase;

    private function przypisanie(?string $end = null): array
    {
        $accountId = Account::create(['name' => 'MKL'])->id;
        $biuro = User::factory()->create([
            'account_id' => $accountId, 'email' => 'biuro@mkl.pl', 'owner' => 2,
            'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
        $budowa = Organization::create(['account_id' => $accountId, 'nazwaBud' => 'Cementownia']);
        $typ = NarzedziaTyp::create(['name' => 'Bus']);
        $narzedzie = Narzedzia::create(['name' => 'Renault Trafic', 'narzedzia_typ_id' => $typ->id, 'ilosc_all' => 1, 'ilosc_magazyn' => 0, 'ilosc_budowa' => 1]);
        $twd = ToolWorkDate::create(['narzedzia_id' => $narzedzie->id, 'organization_id' => $budowa->id, 'narzedzia_nb' => 1, 'start' => '2026-09-13', 'end' => $end]);

        return [$biuro, $budowa, $twd];
    }

    public function test_mozna_ustawic_date_konca_bez_zdejmowania(): void
    {
        [$biuro, $budowa, $twd] = $this->przypisanie(null);

        $this->actingAs($biuro)
            ->put('/budowy/'.$budowa->id.'/narzedzia/'.$twd->id, ['narzedzia_nb' => 1, 'start' => '2026-09-13', 'end' => '2026-10-31'])
            ->assertRedirect('/budowy/'.$budowa->id.'/narzedzia')->assertSessionHasNoErrors();

        $twd->refresh();
        $this->assertSame('2026-10-31', (string) $twd->end);
        $this->assertSame('2026-09-13', (string) $twd->start);
        $this->assertNotNull($twd->id, 'Sprzęt zostaje na budowie, nie jest zdjęty.');
    }

    public function test_pusta_data_konca_zostawia_bez_konca(): void
    {
        [$biuro, $budowa, $twd] = $this->przypisanie('2026-10-31');

        $this->actingAs($biuro)
            ->put('/budowy/'.$budowa->id.'/narzedzia/'.$twd->id, ['narzedzia_nb' => 1, 'start' => '2026-09-13', 'end' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($twd->fresh()->end);
    }

    public function test_data_konca_przed_poczatkiem_odpada(): void
    {
        [$biuro, $budowa, $twd] = $this->przypisanie(null);

        $this->actingAs($biuro)
            ->put('/budowy/'.$budowa->id.'/narzedzia/'.$twd->id, ['narzedzia_nb' => 1, 'start' => '2026-09-13', 'end' => '2026-09-01'])
            ->assertSessionHasErrors('end');

        $this->assertNull($twd->fresh()->end);
    }
}
