<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\ShiftStatus;
use App\Models\User;
use App\Models\WniosekUrlopowy;
use App\Models\ZgloszenieKierownika;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kadry zatwierdzają zgłoszenie kierownika jednym kliknięciem, a zmiana
 * nanosi się sama: urlop → nieobecność (KCP), zjazd → skrócony pobyt,
 * przeniesienie → stary pobyt zamknięty i nowy założony.
 */
class ZatwierdzanieZgloszenTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $kadry;
    private Contact $pracownik;
    private Organization $budowa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->kadry = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kadry@mkl.pl', 'owner' => 6,
            'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
        $this->budowa = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Budowa A']);
        $this->pracownik = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski']);
        foreach (['UW' => 'Urlop wypoczynkowy', 'UŻ' => 'Na żądanie'] as $kod => $tytul) {
            DB::table('shift_status')->insert(['code' => $kod, 'title' => $tytul]);
        }
    }

    private function zgloszenie(array $attrs): ZgloszenieKierownika
    {
        return ZgloszenieKierownika::create(array_merge([
            'contact_id' => $this->pracownik->id,
            'organization_id' => $this->budowa->id,
            'user_id' => $this->kadry->id,
            'rodzaj' => 'urlop',
            'status' => 'nowe',
        ], $attrs));
    }

    public function test_urlop_wstawia_nieobecnosc_i_zamyka_zgloszenie(): void
    {
        $z = $this->zgloszenie(['rodzaj' => 'urlop', 'od' => '2026-10-05', 'do' => '2026-10-09']);

        $this->actingAs($this->kadry)
            ->put('/zgloszenia/'.$z->id.'/zatwierdz', ['kod' => 'UŻ', 'odpowiedz' => 'ok'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $h = Holiday::where('contact_id', $this->pracownik->id)->sole();
        $this->assertSame('2026-10-05', (string) $h->start);
        $this->assertSame((string) ShiftStatus::where('code', 'UŻ')->value('id'), (string) $h->shift_status_id);
        $this->assertSame('obsluzone', $z->fresh()->status);
    }

    public function test_urlop_z_wniosku_bierze_kod_z_wniosku_nie_z_formularza(): void
    {
        $wniosek = WniosekUrlopowy::create([
            'contact_id' => $this->pracownik->id, 'rodzaj' => 'UŻ',
            'od' => '2026-10-05', 'do' => '2026-10-06', 'status' => 'zatwierdzony',
        ]);
        $z = $this->zgloszenie(['rodzaj' => 'urlop', 'od' => '2026-10-05', 'do' => '2026-10-06', 'wniosek_id' => $wniosek->id]);

        // Formularz podsuwa UW, ale wniosek mówi UŻ — wygrywa wniosek.
        $this->actingAs($this->kadry)->put('/zgloszenia/'.$z->id.'/zatwierdz', ['kod' => 'UW'])->assertSessionHasNoErrors();

        $this->assertSame((int) ShiftStatus::where('code', 'UŻ')->value('id'), (int) Holiday::sole()->shift_status_id);
    }

    public function test_zjazd_skraca_pobyt(): void
    {
        $pobyt = ContactWorkDate::create(['contact_id' => $this->pracownik->id, 'organization_id' => $this->budowa->id, 'start' => '2026-09-01', 'end' => null]);
        $z = $this->zgloszenie(['rodzaj' => 'zjazd', 'od' => '2026-10-10', 'do' => '2026-10-10']);

        $this->actingAs($this->kadry)->put('/zgloszenia/'.$z->id.'/zatwierdz', [])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-10', (string) $pobyt->fresh()->end);
        $this->assertSame('obsluzone', $z->fresh()->status);
    }

    public function test_przeniesienie_zamyka_stary_pobyt_i_zaklada_nowy(): void
    {
        $cel = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Budowa B']);
        $pobyt = ContactWorkDate::create(['contact_id' => $this->pracownik->id, 'organization_id' => $this->budowa->id, 'start' => '2026-09-01', 'end' => null]);
        $z = $this->zgloszenie(['rodzaj' => 'przeniesienie', 'od' => '2026-10-15', 'do' => null]);

        $this->actingAs($this->kadry)
            ->put('/zgloszenia/'.$z->id.'/zatwierdz', ['organization_docelowa_id' => $cel->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-14', (string) $pobyt->fresh()->end);
        $nowy = ContactWorkDate::where('contact_id', $this->pracownik->id)->where('organization_id', $cel->id)->sole();
        $this->assertSame('2026-10-15', (string) $nowy->start);
    }

    public function test_przeniesienie_bez_budowy_docelowej_nic_nie_zmienia(): void
    {
        $z = $this->zgloszenie(['rodzaj' => 'przeniesienie', 'od' => '2026-10-15']);

        $this->actingAs($this->kadry)->put('/zgloszenia/'.$z->id.'/zatwierdz', [])->assertSessionHasErrors('zatwierdz');
        $this->assertSame('nowe', $z->fresh()->status);
    }
}
