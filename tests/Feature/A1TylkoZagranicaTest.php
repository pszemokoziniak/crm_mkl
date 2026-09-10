<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\A1;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\KrajTyp;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A1 potwierdza, gdzie pracownik podlega ubezpieczeniu, kiedy firma wysyła go
 * do innego państwa. Na kontrakcie w Polsce nie jest do niczego potrzebne, a
 * system dopominał się o nie przy każdym pracowniku w kraju i pokazywał przy
 * takiej budowie zakładkę, w której nie ma czego pilnować.
 *
 * O tym, które kraje wymagają dokumentu, mówi słownik (Ustawienia → Kraj).
 */
class A1TylkoZagranicaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private KrajTyp $polska;
    private KrajTyp $niemcy;
    private Organization $wKraju;
    private Organization $zaGranica;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => Role::BIURO->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->polska = KrajTyp::create(['name' => 'Polska', 'wymaga_a1' => false]);
        $this->niemcy = KrajTyp::create(['name' => 'Niemcy', 'wymaga_a1' => true]);

        $this->wKraju = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'DROSED Kałuszyn',
            'country_id' => $this->polska->id,
        ]);
        $this->zaGranica = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Berkes Lachendorf',
            'country_id' => $this->niemcy->id,
        ]);
    }

    private function pracownikNa(Organization $budowa, string $nazwisko): Contact
    {
        $osoba = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => $nazwisko,
        ]);

        ContactWorkDate::create([
            'contact_id' => $osoba->id,
            'organization_id' => $budowa->id,
            'start' => now()->subMonth()->toDateString(),
            'end' => now()->addMonth()->toDateString(),
        ]);

        return $osoba;
    }

    private function bezA1(): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($this->biuro)->get('/')->viewData('page')['props']['bez_a1'])
            ->pluck('last_name');
    }

    private function raport(): array
    {
        return $this->actingAs($this->biuro)->get('/reports/koniecUprawinien')
            ->viewData('page')['props'];
    }

    public function test_pulpit_nie_dopomina_sie_o_a1_na_budowie_w_polsce(): void
    {
        $this->pracownikNa($this->wKraju, 'Krajowy');
        $this->pracownikNa($this->zaGranica, 'Wyjezdzajacy');

        $lista = $this->bezA1();

        $this->assertContains('Wyjezdzajacy', $lista);
        $this->assertNotContains('Krajowy', $lista, 'W Polsce A1 nie jest do niczego potrzebne.');
    }

    public function test_budowa_z_nierozpoznanym_krajem_dalej_pilnuje_a1(): void
    {
        // Ostrożniej dopomnieć się niepotrzebnie, niż wysłać kogoś bez dokumentu.
        $bezKraju = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Nowy kontrakt',
            'country_id' => 999,
        ]);
        $this->pracownikNa($bezKraju, 'Niewiadomo');

        $this->assertContains('Niewiadomo', $this->bezA1());
        $this->assertTrue($bezKraju->wymagaA1());
    }

    public function test_raport_nie_pokazuje_braku_a1_dla_pracy_w_kraju(): void
    {
        $this->pracownikNa($this->wKraju, 'Krajowy');
        $this->pracownikNa($this->zaGranica, 'Wyjezdzajacy');

        $braki = collect($this->raport()['braki'])->keyBy(fn ($b) => explode(' ', $b['name'])[0]);

        $this->assertContains('A1', $braki['Wyjezdzajacy']['missing']);
        $this->assertNotContains('A1', $braki['Krajowy']['missing']);
        $this->assertContains('Badania', $braki['Krajowy']['missing'], 'Reszta dokumentów obowiązuje tak samo.');
    }

    public function test_raport_nie_przypomina_o_konczacym_sie_a1_dla_pracy_w_kraju(): void
    {
        $krajowy = $this->pracownikNa($this->wKraju, 'Krajowy');
        $zagraniczny = $this->pracownikNa($this->zaGranica, 'Wyjezdzajacy');

        foreach ([$krajowy, $zagraniczny] as $osoba) {
            A1::create([
                'contact_id' => $osoba->id,
                'start' => now()->subYear()->toDateString(),
                'end' => now()->addDays(10)->toDateString(),
            ]);
        }

        $a1 = collect($this->raport()['data'])->where('category', 'A1')->pluck('last_name');

        $this->assertContains('Wyjezdzajacy', $a1);
        $this->assertNotContains('Krajowy', $a1);
    }

    public function test_zakladka_a1_znika_przy_budowie_w_polsce(): void
    {
        $wKraju = $this->actingAs($this->biuro)->get("/budowy/{$this->wKraju->id}/edit")
            ->viewData('page')['props']['budowa'];
        $zaGranica = $this->actingAs($this->biuro)->get("/budowy/{$this->zaGranica->id}/edit")
            ->viewData('page')['props']['budowa'];

        $this->assertFalse($wKraju['wymaga_a1'], 'Pasek zakładek ukrywa A1 na tej podstawie.');
        $this->assertTrue($zaGranica['wymaga_a1']);
    }

    public function test_wejscie_na_zakladke_a1_w_kraju_odsyla_z_wyjasnieniem(): void
    {
        $this->actingAs($this->biuro)->get("/budowy/{$this->wKraju->id}/a1")
            ->assertRedirect("/budowy/{$this->wKraju->id}/edit")
            ->assertSessionHas('success');

        $this->actingAs($this->biuro)->get("/budowy/{$this->zaGranica->id}/a1")->assertOk();
    }

    public function test_o_wymogu_decyduje_slownik_a_nie_kod(): void
    {
        $this->pracownikNa($this->wKraju, 'Krajowy');

        $this->assertNotContains('Krajowy', $this->bezA1());

        // Zmiana przepisów albo nowy kraj — słownik krajów prowadzi admin.
        $admin = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'admin@mkl.pl',
            'owner' => Role::ADMIN->value, 'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($admin)
            ->put("/krajTyp/{$this->polska->id}", ['name' => 'Polska', 'wymaga_a1' => true])
            ->assertRedirect();

        $this->assertContains('Krajowy', $this->bezA1());
    }
}
