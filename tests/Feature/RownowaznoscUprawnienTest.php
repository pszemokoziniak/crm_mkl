<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\Uprawnienie;
use App\Uprawnienia\Macierz;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Dowód, że wymiana strażników na role (biuro-permission, admin-permission…)
 * na nazwane uprawnienia (`moze:…`) niczego nie zmieniła: dla każdej trasy
 * ze zrzutu sprzed zmiany zbiór ról, które wchodzą, jest ten sam.
 *
 * Zrzut: tests/Fixtures/uprawnienia-przed.json (270 tras, 16.09.2026).
 */
class RownowaznoscUprawnienTest extends TestCase
{
    private const OFFICE = [1, 2, 4, 6];
    private const WSZYSCY = [1, 2, 3, 4, 5, 6];

    /** Kto wchodził przez stary strażnik. */
    private const PRZED = [
        'biuro-permission' => self::OFFICE,
        'admin-permission' => [1],
        'biuro-kierownik-permission' => self::WSZYSCY,
        'self-or-biuro-permission' => self::OFFICE,
    ];

    public function test_kazda_trasa_wpuszcza_te_same_role_co_przed_zmiana(): void
    {
        $przed = json_decode(file_get_contents(base_path('tests/Fixtures/uprawnienia-przed.json')), true);
        $teraz = collect(Router::getRoutes()->getRoutes())->keyBy(fn (Route $r) => $this->klucz($r->uri(), $r->methods()));

        $sprawdzone = 0;
        foreach ($przed as $trasa) {
            $stary = collect($trasa['middleware'])->first(fn ($m) => str_ends_with($m, '-permission'));
            if ($stary === null) {
                continue;
            }

            $klucz = $this->klucz($trasa['uri'], $trasa['methods']);
            $this->assertTrue($teraz->has($klucz), "Trasa zniknęła: $klucz");

            $this->assertSame(
                self::PRZED[$stary],
                $this->ktoWchodzi($teraz[$klucz]),
                "Inny zbiór ról dla $klucz (było: $stary)",
            );
            $sprawdzone++;
        }

        $this->assertGreaterThan(200, $sprawdzone);
    }

    public function test_zadna_trasa_nie_uzywa_juz_straznika_na_role(): void
    {
        foreach (Router::getRoutes()->getRoutes() as $r) {
            foreach ($r->gatherMiddleware() as $m) {
                $this->assertStringEndsNotWith('-permission', $m, $r->uri());
            }
        }
    }

    public function test_kazde_uprawnienie_w_trasach_istnieje(): void
    {
        foreach (Router::getRoutes()->getRoutes() as $r) {
            foreach ($r->gatherMiddleware() as $m) {
                if (preg_match('/^(moze|wlasny-profil-lub):(.+)$/', $m, $x)) {
                    foreach (explode(',', $x[2]) as $nazwa) {
                        $this->assertNotNull(Uprawnienie::tryFrom($nazwa), "Nieznane uprawnienie '$nazwa' na {$r->uri()}");
                    }
                }
            }
        }
    }

    /** @param string[] $metody */
    private function klucz(string $uri, array $metody): string
    {
        $metody = array_values(array_diff($metody, ['HEAD']));
        sort($metody);

        return implode('|', $metody).' '.$uri;
    }

    /** @return int[] wartości ról, które strażnik `moze:` wpuszcza (bez zakresu) */
    private function ktoWchodzi(Route $r): array
    {
        $wymagane = [];
        foreach ($r->gatherMiddleware() as $m) {
            if (preg_match('/^(moze|wlasny-profil-lub):(.+)$/', $m, $x)) {
                foreach (explode(',', $x[2]) as $nazwa) {
                    $wymagane[] = Uprawnienie::from($nazwa);
                }
            }
        }
        $this->assertNotEmpty($wymagane, 'Trasa bez strażnika: '.$r->uri());

        $role = [];
        foreach (Role::cases() as $rola) {
            $ma = true;
            foreach ($wymagane as $u) {
                $ma = $ma && Macierz::ma($rola, $u);
            }
            if ($ma) {
                $role[] = $rola->value;
            }
        }
        sort($role);

        return $role;
    }
}
