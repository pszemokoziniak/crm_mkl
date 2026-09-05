<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Archiwizacja budowy: zakończone pobyty pracowników nie mogą blokować,
 * trwające i przyszłe — muszą.
 */
class ArchiwizacjaBudowyTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = $this->user(2, 'biuro@example.com');
        $this->admin = $this->user(1, 'admin@example.com');
    }

    public function test_budowa_z_samymi_zakonczonymi_pobytami_da_sie_zarchiwizowac(): void
    {
        $budowa = $this->budowa('434_Valmet Reko Holandia');
        $this->pobyt($budowa, '2024-05-06', '2024-09-30');
        $this->pobyt($budowa, '2024-06-08', '2024-09-30');

        $this->actingAs($this->biuro)
            ->delete('/budowy/'.$budowa->id)
            ->assertRedirect();

        $this->assertSoftDeleted($budowa);
        // Historia pobytów zostaje — karmi raporty i karty pracowników.
        $this->assertSame(2, ContactWorkDate::where('organization_id', $budowa->id)->count());
    }

    public function test_trwajacy_pobyt_blokuje_i_komunikat_wskazuje_kogo_dotyczy(): void
    {
        $budowa = $this->budowa('515_Siemens Grudziądz');
        $this->pobyt($budowa, '2024-01-01', '2024-02-01');
        $this->pobyt($budowa, Carbon::today()->subMonth()->toDateString(), Carbon::today()->addMonths(2)->toDateString(), 'Nowak', 'Jan');

        $response = $this->actingAs($this->biuro)->delete('/budowy/'.$budowa->id);

        $response->assertRedirect();
        $this->assertNull($budowa->fresh()->deleted_at);
        $this->assertStringContainsString('Nowak Jan', session('error'));
    }

    public function test_przyszly_pobyt_tez_blokuje(): void
    {
        $budowa = $this->budowa('Przyszła budowa');
        $this->pobyt(
            $budowa,
            Carbon::today()->addMonth()->toDateString(),
            Carbon::today()->addMonths(3)->toDateString()
        );

        $this->actingAs($this->biuro)->delete('/budowy/'.$budowa->id);

        $this->assertNull($budowa->fresh()->deleted_at);
    }

    public function test_pobyt_bez_daty_konca_blokuje(): void
    {
        $budowa = $this->budowa('Bez daty końca');
        $this->pobyt($budowa, '2024-01-01', null);

        $this->actingAs($this->biuro)->delete('/budowy/'.$budowa->id);

        $this->assertNull($budowa->fresh()->deleted_at);
    }

    public function test_pobyt_konczacy_sie_dzis_jeszcze_blokuje(): void
    {
        $budowa = $this->budowa('Kończy się dziś');
        $this->pobyt($budowa, '2024-01-01', Carbon::today()->toDateString());

        $this->actingAs($this->biuro)->delete('/budowy/'.$budowa->id);

        $this->assertNull($budowa->fresh()->deleted_at);
    }

    public function test_admin_moze_wymusic_archiwizacje_biuro_nie(): void
    {
        $budowa = $this->budowa('Z trwającym pobytem');
        $this->pobyt($budowa, '2024-01-01', Carbon::today()->addMonth()->toDateString());

        // Biuro nie przepchnie tego nawet z force.
        $this->actingAs($this->biuro)->delete('/budowy/'.$budowa->id, ['force' => true]);
        $this->assertNull($budowa->fresh()->deleted_at);

        $this->actingAs($this->admin)->delete('/budowy/'.$budowa->id, ['force' => true]);
        $this->assertSoftDeleted($budowa);
    }

    public function test_budowa_bez_pracownikow_da_sie_zarchiwizowac(): void
    {
        $budowa = $this->budowa('Pusta budowa');

        $this->actingAs($this->biuro)->delete('/budowy/'.$budowa->id);

        $this->assertSoftDeleted($budowa);
    }

    public function test_lista_bud_oznacza_kandydatow_do_archiwizacji(): void
    {
        $zakonczona = $this->budowa('Zakończona');
        $this->pobyt($zakonczona, '2024-05-06', '2024-09-30');

        $trwajaca = $this->budowa('Trwająca');
        $this->pobyt($trwajaca, '2024-05-06', Carbon::today()->addMonth()->toDateString());

        $pusta = $this->budowa('Bez pracowników');

        $this->actingAs($this->biuro)
            ->get('/budowy?sort=nazwaBud&direction=asc')
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $budowy = collect($page->toArray()['props']['organizations']['data'])
                    ->keyBy('nazwaBud');

                $this->assertTrue($budowy['Zakończona']['ready_to_archive']);
                $this->assertFalse($budowy['Trwająca']['ready_to_archive']);
                // Budowa bez żadnych pobytów to nie "kandydat", tylko pusty wpis.
                $this->assertFalse($budowy['Bez pracowników']['ready_to_archive']);
            });

        unset($pusta);
    }

    public function test_lista_pracownikow_rozroznia_trwajace_i_zakonczone_pobyty(): void
    {
        $budowa = $this->budowa('Mieszana');
        $this->pobyt($budowa, '2024-05-06', '2024-09-30', 'Chojecki', 'Daniel');
        $this->pobyt($budowa, '2024-05-06', Carbon::today()->addMonth()->toDateString(), 'Nowak', 'Jan');

        $this->actingAs($this->biuro)
            ->get('/pracownicy/'.$budowa->id)
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                // Szukamy po nazwisku, nie po pozycji — lista trzyma teraz
                // obecnych na budowie przed tymi, którzy zjechali.
                $wpisy = collect($page->toArray()['props']['contactworkdates']['data'])
                    ->keyBy(fn ($w) => $w['contact']['last_name']);

                $this->assertFalse($wpisy['Chojecki']['on_site']);
                $this->assertTrue($wpisy['Nowak']['on_site']);
            });
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => $owner,
            'active' => 1,
        ]);
    }

    private function budowa(string $nazwa): Organization
    {
        return Organization::create([
            'account_id' => $this->accountId,
            'name' => $nazwa,
            'nazwaBud' => $nazwa,
        ]);
    }

    private function pobyt(Organization $budowa, string $start, ?string $end, string $nazwisko = 'Kowalski', string $imie = 'Adam'): ContactWorkDate
    {
        $contact = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => $imie,
            'last_name' => $nazwisko,
        ]);

        return ContactWorkDate::create([
            'contact_id' => $contact->id,
            'organization_id' => $budowa->id,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
