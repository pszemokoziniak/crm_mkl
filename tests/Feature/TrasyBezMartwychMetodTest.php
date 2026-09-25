<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Trasa wskazująca na nieistniejącą metodę kontrolera daje 500 dopiero przy
 * wejściu na stronę. Tak przez dwa lata wisiało GET prognoza/building
 * (metoda building() usunięta w 2024, trasa została).
 */
class TrasyBezMartwychMetodTest extends TestCase
{
    use RefreshDatabase;

    public function test_kazda_trasa_wskazuje_na_istniejaca_metode_kontrolera(): void
    {
        $martwe = collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => is_string($r->getAction('uses')))
            ->reject(function (Route $r) {
                [$klasa, $metoda] = array_pad(explode('@', $r->getAction('uses')), 2, '__invoke');

                return class_exists($klasa) && method_exists($klasa, $metoda);
            })
            ->map(fn (Route $r) => implode('|', $r->methods()).' '.$r->uri().' → '.$r->getAction('uses'))
            ->values()
            ->all();

        $this->assertSame([], $martwe);
    }

    public function test_prognoza_building_nie_daje_juz_bledu_serwera(): void
    {
        $admin = User::factory()->create(['owner' => Role::ADMIN->value, 'active' => 1]);

        $this->actingAs($admin)->get('/prognoza/building')->assertStatus(405);
    }
}
