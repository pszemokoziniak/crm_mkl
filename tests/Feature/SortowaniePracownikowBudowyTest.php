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
 * Lista pracowników budowy: sortowanie kolumnami. Po nazwisku obecni na
 * budowie idą przed tymi, którzy zjechali — inaczej jedni przeplatają się
 * z drugimi i nie widać, kto tam właściwie jest.
 */
class SortowaniePracownikowBudowyTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Organization $budowa;
    private Funkcja $monter;
    private Funkcja $spawacz;

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

        $this->budowa = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => '504_Valmet']);
        $this->monter = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);
        $this->spawacz = Funkcja::create(['name' => 'Spawacz', 'kierownictwo' => false]);
    }

    private function pobyt(string $nazwisko, ?string $koniec, ?Funkcja $funkcja = null): void
    {
        $contact = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
            'funkcja_id' => optional($funkcja)->id,
        ]);

        ContactWorkDate::create([
            'contact_id' => $contact->id,
            'organization_id' => $this->budowa->id,
            'start' => '2026-01-01',
            'end' => $koniec,
        ]);
    }

    private function nazwiska(string $zapytanie = ''): array
    {
        $odpowiedz = $this->actingAs($this->biuro)->get('/pracownicy/'.$this->budowa->id.$zapytanie);
        $odpowiedz->assertOk();

        return collect($odpowiedz->viewData('page')['props']['contactworkdates']['data'])
            ->pluck('contact.last_name')
            ->all();
    }

    public function test_po_nazwisku_obecni_przed_tymi_ktorzy_zjechali(): void
    {
        // Alfabetycznie: Adamski, Borek, Cichy, Dąbek.
        $this->pobyt('Adamski', '2025-06-30');            // zjechał
        $this->pobyt('Borek', now()->addMonth()->toDateString());
        $this->pobyt('Cichy', '2025-01-31');              // zjechał
        $this->pobyt('Dąbek', null);                       // bez daty końca — jest

        $this->assertSame(['Borek', 'Dąbek', 'Adamski', 'Cichy'], $this->nazwiska());
    }

    public function test_odwrotny_kierunek_nie_miesza_grup(): void
    {
        $this->pobyt('Adamski', '2025-06-30');
        $this->pobyt('Borek', now()->addMonth()->toDateString());
        $this->pobyt('Cichy', '2025-01-31');
        $this->pobyt('Dąbek', null);

        // Obecni nadal na górze, tylko wewnątrz grup kolejność odwrócona.
        $this->assertSame(['Dąbek', 'Borek', 'Cichy', 'Adamski'], $this->nazwiska('?sort=nazwisko&direction=desc'));
    }

    public function test_po_dacie_sortuje_wedlug_terminu_zjazdu(): void
    {
        $this->pobyt('Trzeci', '2026-12-31');
        $this->pobyt('Pierwszy', '2026-09-30');
        $this->pobyt('Drugi', '2026-11-15');
        $this->pobyt('Bezterminowy', null);

        $this->assertSame(
            ['Pierwszy', 'Drugi', 'Trzeci', 'Bezterminowy'],
            $this->nazwiska('?sort=data')
        );
    }

    public function test_po_dacie_malejaco(): void
    {
        $this->pobyt('Trzeci', '2026-12-31');
        $this->pobyt('Pierwszy', '2026-09-30');
        $this->pobyt('Drugi', '2026-11-15');

        $this->assertSame(['Trzeci', 'Drugi', 'Pierwszy'], $this->nazwiska('?sort=data&direction=desc'));
    }

    public function test_po_stanowisku(): void
    {
        $this->pobyt('Monterski', now()->addMonth()->toDateString(), $this->monter);
        $this->pobyt('Spawalski', now()->addMonth()->toDateString(), $this->spawacz);

        $this->assertSame(['Monterski', 'Spawalski'], $this->nazwiska('?sort=stanowisko'));
        $this->assertSame(['Spawalski', 'Monterski'], $this->nazwiska('?sort=stanowisko&direction=desc'));
    }

    public function test_nieznane_sortowanie_wraca_do_nazwiska(): void
    {
        $this->pobyt('Adamski', '2025-06-30');
        $this->pobyt('Borek', now()->addMonth()->toDateString());

        $this->assertSame(['Borek', 'Adamski'], $this->nazwiska('?sort=cokolwiek'));
    }

    public function test_widok_wie_jakie_sortowanie_jest_wybrane(): void
    {
        $this->pobyt('Adamski', null);

        $odpowiedz = $this->actingAs($this->biuro)->get('/pracownicy/'.$this->budowa->id.'?sort=data&direction=desc');

        $this->assertSame(
            ['sort' => 'data', 'direction' => 'desc'],
            $odpowiedz->viewData('page')['props']['sortowanie']
        );
    }
}
