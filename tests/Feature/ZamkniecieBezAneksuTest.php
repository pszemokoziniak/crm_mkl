<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZmianaKadrowa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zmiany kadrowe: nie każdą sprawę zamyka aneks. Zjazd z budowy bez
 * kolejnej roboty trzeba dać się zamknąć bez dokumentu, a nazwisko
 * pracownika ma zostać także po przeniesieniu go do archiwum.
 */
class ZamkniecieBezAneksuTest extends TestCase
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
            'account_id' => $this->accountId,
            'email' => 'kadry@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->budowa = Organization::create(['account_id' => 0, 'name' => 'Lausitzer', 'nazwaBud' => 'Lausitzer Zeitz']);

        $this->pracownik = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Łukasz',
            'last_name' => 'Bogusz',
        ]);
    }

    private function wpis(string $status = ZmianaKadrowa::STATUS_NOWA): ZmianaKadrowa
    {
        return ZmianaKadrowa::create([
            'contact_id' => $this->pracownik->id,
            'typ' => ZmianaKadrowa::TYP_USUNIECIE,
            'organization_from_id' => $this->budowa->id,
            'old_start' => '2026-09-01',
            'old_end' => '2026-10-31',
            'paczka' => 'p1',
            'status' => $status,
            'changed_by' => $this->kadry->id,
        ]);
    }

    private function paczki(): array
    {
        $odpowiedz = $this->actingAs($this->kadry)->get('/zmiany-kadrowe');
        $odpowiedz->assertOk();

        return $odpowiedz->viewData('page')['props']['paczki'];
    }

    public function test_zamkniecie_bez_aneksu_zdejmuje_sprawe_z_listy(): void
    {
        $wpis = $this->wpis();

        $this->actingAs($this->kadry)
            ->put('/zmiany-kadrowe', ['id' => $wpis->id, 'status' => ZmianaKadrowa::STATUS_BEZ_ANEKSU])
            ->assertRedirect();

        $wpis->refresh();

        $this->assertSame(ZmianaKadrowa::STATUS_BEZ_ANEKSU, $wpis->status);
        $this->assertSame($this->kadry->id, $wpis->handled_by);
        $this->assertNotNull($wpis->handled_at);
        $this->assertSame(0, ZmianaKadrowa::nieobsluzone()->count());
        $this->assertSame([], $this->paczki());
    }

    public function test_zamkniete_bez_aneksu_widac_w_zakladce_wszystkie(): void
    {
        $wpis = $this->wpis(ZmianaKadrowa::STATUS_BEZ_ANEKSU);

        $odpowiedz = $this->actingAs($this->kadry)->get('/zmiany-kadrowe?pokaz=wszystkie');
        $paczki = $odpowiedz->viewData('page')['props']['paczki'];

        $this->assertSame('Zamknięta bez aneksu', $paczki[0]['zmiany'][0]['status_label']);
        $this->assertSame(0, $paczki[0]['nieobsluzonych']);
    }

    public function test_umowa_gotowa_dziala_jak_dotad(): void
    {
        $wpis = $this->wpis();

        $this->actingAs($this->kadry)
            ->put('/zmiany-kadrowe', ['id' => $wpis->id, 'status' => ZmianaKadrowa::STATUS_GOTOWA]);

        $this->assertSame(ZmianaKadrowa::STATUS_GOTOWA, $wpis->fresh()->status);
        $this->assertSame(0, ZmianaKadrowa::nieobsluzone()->count());
    }

    public function test_nazwisko_zostaje_po_przeniesieniu_do_archiwum(): void
    {
        $this->wpis();
        $this->pracownik->delete();

        $wiersz = $this->paczki()[0]['zmiany'][0];

        $this->assertSame('Bogusz Łukasz', $wiersz['pracownik']);
        $this->assertTrue($wiersz['pracownik_w_archiwum']);
    }

    public function test_pracownik_w_bazie_nie_jest_oznaczony_jako_archiwum(): void
    {
        $this->wpis();

        $this->assertFalse($this->paczki()[0]['zmiany'][0]['pracownik_w_archiwum']);
    }

    public function test_nieznany_status_jest_odrzucany(): void
    {
        $wpis = $this->wpis();

        $this->actingAs($this->kadry)
            ->put('/zmiany-kadrowe', ['id' => $wpis->id, 'status' => 'usuniete'])
            ->assertSessionHasErrors('status');

        $this->assertSame(ZmianaKadrowa::STATUS_NOWA, $wpis->fresh()->status);
    }
}
