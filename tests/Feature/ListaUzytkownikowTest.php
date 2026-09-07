<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lista użytkowników: nazwisko przed imieniem, kolejność polskim alfabetem
 * i filtr uprawnień, który faktycznie filtruje (dotąd zwracał komplet).
 */
class ListaUzytkownikowTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->admin = $this->uzytkownik('Adamiak', 'Anna', Role::ADMIN->value, 'admin@mkl.pl');
    }

    private function uzytkownik(string $nazwisko, string $imie, int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'first_name' => $imie,
            'last_name' => $nazwisko,
            'email' => $email,
            'owner' => $owner,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function lista(array $filtry = []): array
    {
        $adres = '/users'.($filtry ? '?'.http_build_query($filtry) : '');

        return $this->actingAs($this->admin)->get($adres)->viewData('page')["props"]["users"];
    }

    public function test_lista_niesie_nazwisko_i_imie_osobno(): void
    {
        $wiersz = collect($this->lista())->firstWhere('last_name', 'Adamiak');

        // Widok składa "Nazwisko Imię", więc potrzebuje obu pól osobno.
        $this->assertSame('Adamiak', $wiersz['last_name']);
        $this->assertSame('Anna', $wiersz['first_name']);
    }

    public function test_kolejnosc_wedlug_nazwiska(): void
    {
        $this->uzytkownik('Zawadzki', 'Piotr', Role::BIURO->value, 'z@mkl.pl');
        $this->uzytkownik('Kowalski', 'Jan', Role::BIURO->value, 'k@mkl.pl');
        $this->uzytkownik('Nowak', 'Ewa', Role::BIURO->value, 'n@mkl.pl');

        $nazwiska = collect($this->lista())->pluck('last_name')->all();

        $this->assertSame(['Adamiak', 'Kowalski', 'Nowak', 'Zawadzki'], $nazwiska);
    }

    public function test_to_samo_nazwisko_rozstrzyga_imie(): void
    {
        $this->uzytkownik('Nowak', 'Zofia', Role::BIURO->value, 'nz@mkl.pl');
        $this->uzytkownik('Nowak', 'Ewa', Role::BIURO->value, 'ne@mkl.pl');

        $nowakowie = collect($this->lista())->where('last_name', 'Nowak')->pluck('first_name')->values()->all();

        $this->assertSame(['Ewa', 'Zofia'], $nowakowie);
    }

    public function test_polskie_znaki_ida_w_swoim_miejscu(): void
    {
        foreach ([
            ['Szafranski', 'Jan', 'sza@mkl.pl'],
            ['Śledź', 'Adam', 'sle@mkl.pl'],
            ['Skoczylas', 'Ewa', 'sko@mkl.pl'],
            ['Sobiczewski', 'Piotr', 'sob@mkl.pl'],
            ['Żurek', 'Anna', 'zur@mkl.pl'],
            ['Zawadzki', 'Marek', 'zaw@mkl.pl'],
        ] as [$n, $i, $e]) {
            $this->uzytkownik($n, $i, Role::BIURO->value, $e);
        }

        $nazwiska = collect($this->lista())->pluck('last_name')->all();

        // Ś to osobna litera PO całym S — więc "Szafranski" (S...) wyprzedza
        // "Śledź" (Ś...). Tak samo Ż po całym Z. Przy utf8mb4_unicode_ci
        // Ś było równe S i "Śledź" lądował między "Skoczylas" a "Szafranski".
        $this->assertSame(
            ['Adamiak', 'Skoczylas', 'Sobiczewski', 'Szafranski', 'Śledź', 'Zawadzki', 'Żurek'],
            $nazwiska
        );
    }

    public function test_filtr_uprawnien_naprawde_filtruje(): void
    {
        $this->uzytkownik('Biurowa', 'Beata', Role::BIURO->value, 'b@mkl.pl');
        $this->uzytkownik('Kierownikowski', 'Karol', Role::KIEROWNIK->value, 'kb@mkl.pl');
        $this->uzytkownik('Zarzadowa', 'Zofia', Role::KIEROWNICTWO->value, 'zz@mkl.pl');

        // Dotąd każdy z tych filtrów zwracał komplet użytkowników.
        $this->assertSame(['Biurowa'], collect($this->lista(['role' => Role::BIURO->value]))->pluck('last_name')->all());
        $this->assertSame(['Kierownikowski'], collect($this->lista(['role' => Role::KIEROWNIK->value]))->pluck('last_name')->all());
        $this->assertSame(['Zarzadowa'], collect($this->lista(['role' => Role::KIEROWNICTWO->value]))->pluck('last_name')->all());
        $this->assertSame(['Adamiak'], collect($this->lista(['role' => Role::ADMIN->value]))->pluck('last_name')->all());
        $this->assertCount(4, $this->lista());
    }

    public function test_szukanie_po_nazwisku_dziala_dalej(): void
    {
        $this->uzytkownik('Kowalski', 'Jan', Role::BIURO->value, 'k@mkl.pl');

        $this->assertSame(['Kowalski'], collect($this->lista(['search' => 'kowal']))->pluck('last_name')->all());
    }
}
