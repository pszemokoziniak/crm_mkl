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
 * Zakładka widoczna w menu musi się otwierać, a otwierająca się — być
 * widoczna. Rozjazd w jedną stronę zgubił kierownikowi projektu Budowy,
 * w drugą zostawiał mu zakładkę Umowa, na którą serwer odpowiadał odmową.
 *
 * Oba wzięły się z pytania o numer roli zamiast o to, co ta osoba robi.
 */
class ZakladkiRolTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        Funkcja::create([
            'id' => Funkcja::KIEROWNIK, 'name' => 'Kierownik Budowy',
            'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK,
        ]);
    }

    /** @return array{0: User, 1: Contact} kierownik i pracownik na jego budowie */
    private function zespol(int $owner): array
    {
        $budowa = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Budowa '.$owner]);

        $user = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'rola'.$owner.'@mkl.pl',
            'first_name' => 'Jan', 'last_name' => 'Szef'.$owner,
            'owner' => $owner, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $szef = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Szef'.$owner,
            'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $user->id,
        ]);

        ContactWorkDate::create([
            'contact_id' => $szef->id, 'organization_id' => $budowa->id,
            'start' => now()->subMonth()->toDateString(), 'end' => null,
        ]);

        if ($owner === 5) {
            $budowa->update(['kierownik_projektu_id' => $szef->id]);
        }

        $pracownik = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => 'Monter',
        ]);

        ContactWorkDate::create([
            'contact_id' => $pracownik->id, 'organization_id' => $budowa->id,
            'start' => now()->subMonth()->toDateString(), 'end' => null,
        ]);

        return [$user, $pracownik];
    }

    /**
     * @dataProvider roleProwadzaceBudowy
     */
    public function test_umowa_jest_ukryta_dla_prowadzacych_budowy(int $owner): void
    {
        [$user, $pracownik] = $this->zespol($owner);

        // Serwer odmawia — więc menu nie ma prawa tej pozycji pokazywać.
        $this->actingAs($user)->get('/contacts/'.$pracownik->id.'/umowa')->assertForbidden();

        $props = $this->actingAs($user)
            ->get('/contacts/'.$pracownik->id.'/edit')
            ->viewData('page')['props'];

        $this->assertTrue($props['permissions']['kierownik'], 'Na tym znaczniku stoi ukrycie zakładki.');
        $this->assertFalse($props['permissions']['biuro']);
    }

    /**
     * @dataProvider roleProwadzaceBudowy
     */
    public function test_pozostale_zakladki_pracownika_sie_otwieraja(int $owner): void
    {
        [$user, $pracownik] = $this->zespol($owner);

        foreach (['edit', 'badania', 'bhp', 'pbioz', 'uprawnienia', 'documents', 'jezyk', 'a1', 'holiday', 'history'] as $zakladka) {
            $this->actingAs($user)
                ->get('/contacts/'.$pracownik->id.'/'.$zakladka)
                ->assertOk("Zakładka $zakladka jest w menu, więc musi się otwierać.");
        }
    }

    /**
     * @dataProvider roleProwadzaceBudowy
     */
    public function test_wszystkie_zakladki_budowy_sie_otwieraja(int $owner): void
    {
        [$user] = $this->zespol($owner);
        $budowa = Organization::where('nazwaBud', 'Budowa '.$owner)->firstOrFail();

        $adresy = [
            '/pracownicy/'.$budowa->id,
            '/budowy/'.$budowa->id.'/kierownictwo',
            '/budowy/'.$budowa->id.'/edit',
            '/building/'.$budowa->id.'/time-sheet',
            '/budowy/'.$budowa->id.'/klient',
            '/budowy/'.$budowa->id.'/narzedzia',
            '/budowy/'.$budowa->id.'/a1',
            '/budowy/'.$budowa->id.'/prognoza',
        ];

        foreach ($adresy as $adres) {
            $this->actingAs($user)->get($adres)->assertOk("Pasek budowy pokazuje $adres, więc musi się otwierać.");
        }
    }

    /**
     * @dataProvider roleProwadzaceBudowy
     */
    public function test_prognoza_budowy_jest_tylko_do_podgladu_dla_prowadzacych(int $owner): void
    {
        [$user] = $this->zespol($owner);
        $budowa = Organization::where('nazwaBud', 'Budowa '.$owner)->firstOrFail();

        // Zapis jest zabroniony, więc ekran nie może pokazywać formularza.
        $this->actingAs($user)->post('/budowy/'.$budowa->id.'/prognoza', [])->assertForbidden();

        $props = $this->actingAs($user)
            ->get('/budowy/'.$budowa->id.'/prognoza')
            ->viewData('page')['props'];

        $this->assertTrue($props['flag'], 'Kierownik projektu dostawał formularz, którego nie mógł zapisać.');
    }

    /**
     * @dataProvider roleProwadzaceBudowy
     */
    public function test_lista_budow_nie_pokazuje_utworz_prowadzacym(int $owner): void
    {
        [$user] = $this->zespol($owner);

        $this->actingAs($user)->get('/budowy/create')->assertForbidden();

        $props = $this->actingAs($user)->get('/budowy')->viewData('page')['props'];

        $this->assertFalse(
            $props['permissions']['moze']['budowy.zakladanie'] ?? false,
            'Na tym kluczu stoi ukrycie przycisku „Utwórz” na liście budów.'
        );
    }

    public function test_menu_glowne_zgadza_sie_z_dostepem_serwera(): void
    {
        $pozycje = [
            ['/budowy', ['admin', 'biuro', 'kierownik']],
            ['/contacts', ['admin', 'biuro']],
            ['/kierownicy', ['admin', 'biuro']],
            ['/zmiany-kadrowe', ['admin', 'biuro']],
            ['/narzedzia', ['admin', 'biuro']],
            ['/reports/koniecUprawinien', ['admin', 'biuro', 'kierownik']],
            ['/prognoza', ['admin', 'biuro']],
            ['/tools', ['admin', 'biuro']],
            ['/statystyki', ['admin', 'biuro', 'kierownik']],
            ['/building/time-sheet/month-report', ['admin', 'biuro']],
        ];

        foreach ([1, 2, 3, 4, 5, 6] as $owner) {
            $user = User::factory()->create([
                'account_id' => $this->accountId, 'email' => 'menu'.$owner.'@mkl.pl',
                'owner' => $owner, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
            ]);

            $flagi = $user->permissions;

            foreach ($pozycje as [$adres, $widzi]) {
                $wMenu = collect($widzi)->contains(fn ($rola) => $flagi[$rola] ?? false);
                $kod = $this->actingAs($user)->get($adres)->getStatusCode();
                $this->app['auth']->forgetGuards();

                $this->assertSame(
                    $wMenu,
                    $kod === 200,
                    "Rola $owner: menu i serwer nie zgadzają się co do $adres (HTTP $kod)."
                );
            }
        }
    }

    /** @return array<string, array<int, int>> */
    public function roleProwadzaceBudowy(): array
    {
        return [
            'kierownik budowy' => [3],
            'kierownik projektu' => [5],
        ];
    }
}
