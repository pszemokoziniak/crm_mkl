<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZgloszenieKierownika;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Na ekranie Pracownicy budowy page-prop „zgloszenia" (statusy per pracownik)
 * przykrywa globalny słownik, więc listę dokumentów formularz musi dostać
 * osobnym propem — inaczej rozwijana lista „brak dokumentu" jest pusta.
 */
class ListaDokumentowZgloszeniaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pracownicy_budowy_podaja_liste_dokumentow_do_formularza(): void
    {
        $accountId = Account::create(['name' => 'MKL'])->id;
        $biuro = User::factory()->create([
            'account_id' => $accountId, 'email' => 'biuro@mkl.pl', 'owner' => 2,
            'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
        $budowa = Organization::create(['account_id' => $accountId, 'nazwaBud' => 'Budowa']);

        $props = $this->actingAs($biuro)->get('/pracownicy/'.$budowa->id)->viewData('page')['props'];

        $this->assertSame(ZgloszenieKierownika::DOKUMENTY, $props['dokumenty_zgloszen']);
        $this->assertNotEmpty($props['dokumenty_zgloszen']);
        // Globalny słownik został przykryty przez prop „zgloszenia" (statusy).
        $this->assertArrayNotHasKey('dokumenty', (array) $props['zgloszenia']);
    }
}
