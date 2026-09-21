<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\Uprawnienie;
use App\Uprawnienia\Macierz;
use Tests\TestCase;

/**
 * Kierownik nie edytuje nieobecności, ale zgłasza kadrom powrót pracownika
 * (przycisk „Zgłoś kadrom: wrócił wcześniej” w zablokowanej kratce KCP).
 * Ten przycisk działa na tym samym uprawnieniu, co reszta zgłoszeń.
 */
class ZglosPowrotZKcpTest extends TestCase
{
    public function test_kierownik_zglasza_ale_nie_edytuje_nieobecnosci(): void
    {
        foreach ([Role::KIEROWNIK, Role::KIEROWNIK_PROJEKTU] as $rola) {
            $this->assertTrue(Macierz::ma($rola, Uprawnienie::ZGLOSZENIA_WYSYLANIE), $rola->name);
            $this->assertFalse(Macierz::ma($rola, Uprawnienie::NIEOBECNOSCI_EDYCJA), $rola->name);
        }

        // Kadry odwrotnie: edytują nieobecności same, więc nie potrzebują tej drogi.
        $this->assertTrue(Macierz::ma(Role::KADRY, Uprawnienie::NIEOBECNOSCI_EDYCJA));
    }
}
