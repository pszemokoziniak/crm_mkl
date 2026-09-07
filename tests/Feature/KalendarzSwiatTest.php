<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Feast;
use App\Models\KrajTyp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kalendarz dni wolnych. Trasom brakowało sprawdzenia roli — każdy zalogowany,
 * także kierownik budowy, mógł dodawać i kasować święta, a te wpływają na
 * rozliczenie godzin w kartach pracy. Wchodzi się tu wyłącznie z edycji Kraju,
 * a ta jest admin-only, więc trasy dorównują do tego, co interfejs zakładał.
 */
class KalendarzSwiatTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private KrajTyp $kraj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->kraj = KrajTyp::create(['name' => 'Niemcy']);
    }

    private function uzytkownik(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => $owner,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function swieto(): Feast
    {
        return Feast::create([
            'country_id' => $this->kraj->id,
            'name' => 'Boże Narodzenie',
            'date' => '2026-12-25',
        ]);
    }

    /** @dataProvider roleBezDostepu */
    public function test_nie_admin_nie_oglada_kalendarza(int $owner, string $email): void
    {
        $u = $this->uzytkownik($owner, $email);
        $swieto = $this->swieto();
        $k = $this->kraj->id;

        $this->actingAs($u)->get("/country/{$k}/feasts")->assertForbidden();
        $this->actingAs($u)->get("/country/{$k}/feasts/create")->assertForbidden();
        $this->actingAs($u)->get("/country/{$k}/feasts/{$swieto->id}")->assertForbidden();
    }

    /** @dataProvider roleBezDostepu */
    public function test_nie_admin_nie_dodaje_ani_nie_kasuje(int $owner, string $email): void
    {
        $u = $this->uzytkownik($owner, $email);
        $swieto = $this->swieto();
        $k = $this->kraj->id;

        $this->actingAs($u)->post("/country/{$k}/feasts", [
            'country_id' => $k,
            'name' => 'Wstawione bokiem',
            'date' => '2026-08-15',
        ])->assertForbidden();

        $this->actingAs($u)->delete("/country/{$k}/feasts/{$swieto->id}/delete")->assertForbidden();

        $this->assertDatabaseMissing('feasts', ['name' => 'Wstawione bokiem']);
        $this->assertDatabaseHas('feasts', ['id' => $swieto->id]);
    }

    /** @return array<string, array{int, string}> */
    public function roleBezDostepu(): array
    {
        return [
            'kierownik budowy' => [Role::KIEROWNIK->value, 'kierownik@mkl.pl'],
            'biuro' => [Role::BIURO->value, 'biuro@mkl.pl'],
            'kierownictwo' => [Role::KIEROWNICTWO->value, 'zarzad@mkl.pl'],
        ];
    }

    public function test_admin_dalej_zarzadza_kalendarzem(): void
    {
        $admin = $this->uzytkownik(Role::ADMIN->value, 'admin@mkl.pl');
        $k = $this->kraj->id;

        $this->actingAs($admin)->get("/country/{$k}/feasts")->assertOk();
        $this->actingAs($admin)->get("/country/{$k}/feasts/create")->assertOk();

        $this->actingAs($admin)->post("/country/{$k}/feasts", [
            'country_id' => $k,
            'name' => 'Dzień Jedności Niemiec',
            'date' => '2026-10-03',
        ])->assertRedirect();

        $this->assertDatabaseHas('feasts', ['name' => 'Dzień Jedności Niemiec', 'country_id' => $k]);

        $swieto = Feast::where('name', 'Dzień Jedności Niemiec')->first();
        $this->actingAs($admin)->get("/country/{$k}/feasts/{$swieto->id}")->assertOk();
        $this->actingAs($admin)->delete("/country/{$k}/feasts/{$swieto->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('feasts', ['id' => $swieto->id]);
    }

    public function test_zapisujemy_tylko_pola_z_walidacji(): void
    {
        $admin = $this->uzytkownik(Role::ADMIN->value, 'admin@mkl.pl');
        $k = $this->kraj->id;

        // Dotąd szło tu $request->all(), więc doklejone pole trafiało wprost
        // do zapisu (Model::unguard() jest włączony globalnie).
        $this->actingAs($admin)->post("/country/{$k}/feasts", [
            'country_id' => $k,
            'name' => 'Wielkanoc',
            'date' => '2026-04-05',
            'id_doklejone' => 'cokolwiek',
        ])->assertRedirect();

        $this->assertDatabaseHas('feasts', ['name' => 'Wielkanoc']);
    }

    public function test_niezalogowany_nie_wchodzi(): void
    {
        $this->get("/country/{$this->kraj->id}/feasts")->assertRedirect('/login');
    }
}
