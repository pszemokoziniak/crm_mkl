<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Artykul;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Baza wiedzy: instrukcje w systemie zamiast w mailach. Czyta każdy zalogowany,
 * pisze administrator, a artykuły techniczne (ścieżki na serwerze, polecenia)
 * dla reszty firmy mają w ogóle nie istnieć.
 */
class BazaWiedzyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();

        $accountId = Account::create(['name' => 'MKL'])->id;

        $this->admin = User::factory()->create([
            'account_id' => $accountId,
            'email' => 'admin@mkl.pl',
            'owner' => 1,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->biuro = User::factory()->create([
            'account_id' => $accountId,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function artykul(array $dane = []): Artykul
    {
        return Artykul::create($dane + [
            'tytul' => 'Jak dodać pracownika',
            'kategoria' => 'Instrukcje',
            'tresc' => "## Krok pierwszy\n\nWejdź w **Pracownicy**.",
            'tylko_admin' => false,
            'kolejnosc' => 0,
        ]);
    }

    public function test_kazdy_zalogowany_wchodzi_do_bazy_wiedzy(): void
    {
        $this->artykul();

        $this->actingAs($this->biuro)
            ->get('/baza-wiedzy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('BazaWiedzy/Index')->etc());
    }

    public function test_niezalogowany_nie_wchodzi(): void
    {
        $this->get('/baza-wiedzy')->assertRedirect('/login');
    }

    public function test_artykul_tylko_dla_admina_nie_pokazuje_sie_na_liscie(): void
    {
        $this->artykul(['tytul' => 'Jawny']);
        $this->artykul(['tytul' => 'Techniczny', 'tylko_admin' => true]);

        $tytuly = fn (User $u) => collect(
            $this->actingAs($u)->get('/baza-wiedzy')->viewData('page')['props']['artykuly']
        )->pluck('tytul');

        // Poza tymi dwoma jest jeszcze artykuł zakładany przez migrację,
        // więc sprawdzamy obecność, a nie całą zawartość listy.
        $this->assertContains('Jawny', $tytuly($this->biuro));
        $this->assertNotContains('Techniczny', $tytuly($this->biuro));
        $this->assertContains('Jawny', $tytuly($this->admin));
        $this->assertContains('Techniczny', $tytuly($this->admin));
    }

    public function test_artykul_tylko_dla_admina_jest_niedostepny_takze_z_linku(): void
    {
        $a = $this->artykul(['tylko_admin' => true]);

        // 404, nie 403 — 403 zdradzałoby, że pod tym adresem coś jest.
        $this->actingAs($this->biuro)->get("/baza-wiedzy/{$a->id}")->assertNotFound();
        $this->actingAs($this->admin)->get("/baza-wiedzy/{$a->id}")->assertOk();
    }

    public function test_markdown_zamienia_sie_na_html(): void
    {
        $a = $this->artykul(['tresc' => "## Nagłówek\n\n- punkt\n\n`polecenie`"]);

        $html = $this->actingAs($this->admin)
            ->get("/baza-wiedzy/{$a->id}")
            ->viewData('page')['props']['artykul']['html'];

        $this->assertStringContainsString('<h2>Nagłówek</h2>', $html);
        $this->assertStringContainsString('<li>punkt</li>', $html);
        $this->assertStringContainsString('<code>polecenie</code>', $html);
    }

    public function test_surowy_html_w_tresci_nie_trafia_na_ekran(): void
    {
        $a = $this->artykul(['tresc' => 'Tekst <script>alert(1)</script> dalej']);

        $html = $this->actingAs($this->admin)
            ->get("/baza-wiedzy/{$a->id}")
            ->viewData('page')['props']['artykul']['html'];

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_tylko_admin_dodaje_i_edytuje(): void
    {
        $this->actingAs($this->biuro)->get('/baza-wiedzy/create')->assertForbidden();
        $this->actingAs($this->biuro)->post('/baza-wiedzy', ['tytul' => 'Cudze'])->assertForbidden();

        $this->actingAs($this->admin)->get('/baza-wiedzy/create')->assertOk();
        $this->actingAs($this->admin)->post('/baza-wiedzy', [
            'tytul' => 'Nowa instrukcja',
            'kategoria' => 'Instrukcje',
            'tresc' => 'treść',
            'tylko_admin' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('baza_wiedzy', ['tytul' => 'Nowa instrukcja', 'kolejnosc' => 0]);
    }

    public function test_biuro_nie_usunie_artykulu(): void
    {
        $a = $this->artykul();

        $this->actingAs($this->biuro)->delete("/baza-wiedzy/{$a->id}")->assertForbidden();
        $this->assertDatabaseHas('baza_wiedzy', ['id' => $a->id, 'deleted_at' => null]);

        $this->actingAs($this->admin)->delete("/baza-wiedzy/{$a->id}")->assertRedirect('/baza-wiedzy');
        $this->assertSoftDeleted('baza_wiedzy', ['id' => $a->id]);
    }

    public function test_tytul_jest_wymagany(): void
    {
        $this->actingAs($this->admin)
            ->post('/baza-wiedzy', ['tytul' => '', 'tresc' => 'coś'])
            ->assertSessionHasErrors('tytul');
    }

    public function test_szukanie_obejmuje_tytul_i_tresc(): void
    {
        $this->artykul(['tytul' => 'Wymiana opon w koparce', 'tresc' => 'moment dokręcania 320 Nm']);
        $this->artykul(['tytul' => 'Jak dodać budowę', 'tresc' => 'zupełnie co innego']);

        $tytuly = fn (string $fraza) => collect(
            $this->actingAs($this->admin)->get('/baza-wiedzy?szukaj='.$fraza)->viewData('page')['props']['artykuly']
        )->pluck('tytul')->all();

        $this->assertSame(['Wymiana opon w koparce'], $tytuly('320 Nm'), 'Szukanie ma sięgać treści.');
        $this->assertSame(['Wymiana opon w koparce'], $tytuly('koparce'), 'I tytułu.');
        $this->assertSame([], $tytuly('tego na pewno nigdzie nie ma'));
    }

    public function test_artykul_o_backupie_wjezdza_z_migracja(): void
    {
        // Migracja danych już się wykonała w ramach RefreshDatabase.
        $artykul = Artykul::where('tytul', 'like', 'Kopie zapasowe HRM%')->first();

        $this->assertNotNull($artykul, 'Migracja miała założyć artykuł o kopiach zapasowych.');
        $this->assertTrue($artykul->tylko_admin, 'Instrukcja z poleceniami serwerowymi nie jest dla wszystkich.');
        $this->assertStringContainsString('/var/backups/hrm', $artykul->tresc);
        $this->assertStringContainsString('<table>', $artykul->html(), 'Tabela w markdownie ma się renderować.');
    }
}
