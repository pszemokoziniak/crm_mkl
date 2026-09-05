<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kolizja terminów rozróżnia dwie sytuacje:
 * ten sam pracownik drugi raz na TEJ SAMEJ budowie to zawsze pomyłka,
 * a pobyt na INNEJ budowie wolno mieć tylko kierownictwu.
 */
class PowtorneprzypisanieTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Organization $budowaA;
    private Organization $budowaB;
    private Funkcja $koordynator;
    private Funkcja $monter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->budowaA = Organization::create(['account_id' => 0, 'name' => 'Berkes', 'nazwaBud' => 'Berkes Lachendorf']);
        $this->budowaB = Organization::create(['account_id' => 0, 'name' => 'Vyncke', 'nazwaBud' => 'Vyncke Świdno']);

        $this->koordynator = Funkcja::create(['name' => 'Koordynator ds. Realizacji', 'kierownictwo' => true]);
        $this->monter = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);
    }

    private function pracownik(Funkcja $funkcja): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Szymon',
            'last_name' => 'Paśnikowski',
            'funkcja_id' => $funkcja->id,
        ]);
    }

    private function pobyt(Contact $c, Organization $o, string $start, string $end): ContactWorkDate
    {
        return ContactWorkDate::create([
            'contact_id' => $c->id,
            'organization_id' => $o->id,
            'start' => $start,
            'end' => $end,
        ]);
    }

    private function przypisz(Contact $c, Organization $o, string $start, string $end)
    {
        return $this->actingAs($this->biuro)->post('/contacts/'.$c->id.'/przypisz-budowe', [
            'organization_id' => $o->id,
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function test_kierownictwa_nie_da_sie_przypisac_dwa_razy_do_tej_samej_budowy(): void
    {
        $c = $this->pracownik($this->koordynator);
        $this->pobyt($c, $this->budowaA, '2026-09-01', '2026-12-22');

        $this->przypisz($c, $this->budowaA, '2026-09-01', '2026-12-22')->assertRedirect();

        $this->assertSame(1, ContactWorkDate::where('contact_id', $c->id)->count());
        $this->assertStringContainsString('już przypisany do tej budowy', session('error'));
    }

    public function test_montera_tez_nie_da_sie_przypisac_dwa_razy(): void
    {
        $c = $this->pracownik($this->monter);
        $this->pobyt($c, $this->budowaA, '2026-09-01', '2026-12-22');

        $this->przypisz($c, $this->budowaA, '2026-10-01', '2026-11-30');

        $this->assertSame(1, ContactWorkDate::where('contact_id', $c->id)->count());
        $this->assertStringContainsString('już przypisany do tej budowy', session('error'));
    }

    public function test_kierownictwo_dalej_moze_byc_na_dwoch_budowach(): void
    {
        $c = $this->pracownik($this->koordynator);
        $this->pobyt($c, $this->budowaA, '2026-09-01', '2026-12-22');

        $this->przypisz($c, $this->budowaB, '2026-10-01', '2026-11-30');

        $this->assertSame(2, ContactWorkDate::where('contact_id', $c->id)->count());
        $this->assertNull(session('error'));
    }

    public function test_monter_nadal_blokowany_na_innej_budowie(): void
    {
        $c = $this->pracownik($this->monter);
        $this->pobyt($c, $this->budowaA, '2026-09-01', '2026-12-22');

        $this->przypisz($c, $this->budowaB, '2026-10-01', '2026-11-30');

        $this->assertSame(1, ContactWorkDate::where('contact_id', $c->id)->count());
        $this->assertStringContainsString('Berkes Lachendorf', session('error'));
    }

    public function test_termin_bez_kolizji_przechodzi(): void
    {
        $c = $this->pracownik($this->monter);
        $this->pobyt($c, $this->budowaA, '2026-01-01', '2026-06-30');

        $this->przypisz($c, $this->budowaA, '2026-09-01', '2026-12-22');

        $this->assertSame(2, ContactWorkDate::where('contact_id', $c->id)->count());
    }

    public function test_ta_sama_regula_w_kierownictwie_budowy(): void
    {
        $c = $this->pracownik($this->koordynator);
        $this->pobyt($c, $this->budowaA, '2026-09-01', '2026-12-22');

        $this->actingAs($this->biuro)
            ->post('/budowy/'.$this->budowaA->id.'/kierownictwo', [
                'contact_id' => $c->id,
                'start' => '2026-10-01',
                'end' => '2026-11-30',
            ]);

        $this->assertSame(1, ContactWorkDate::where('contact_id', $c->id)->count());
        $this->assertStringContainsString('już przypisany do tej budowy', session('error'));
    }

    public function test_zarchiwizowana_budowa_w_komunikacie_nie_straszy(): void
    {
        $c = $this->pracownik($this->monter);
        $this->pobyt($c, $this->budowaA, '2026-09-01', '2026-12-22');
        // Budowa w archiwum — pobyt zostaje, ale nazwy już nie widać.
        $this->budowaA->delete();

        $this->przypisz($c, $this->budowaB, '2026-10-01', '2026-11-30');

        $this->assertStringContainsString('już usunięta', session('error'));
    }
}
