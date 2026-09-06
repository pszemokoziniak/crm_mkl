<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formularze podstron pracownika (dodawanie urlopu, badań, uprawnień…)
 * mają mówić, czyją kartę wypełniamy — dotąd był tam sam tytuł działu.
 */
class NaglowkiPracownikaTest extends TestCase
{
    use RefreshDatabase;

    private User $biuro;
    private Contact $pracownik;

    protected function setUp(): void
    {
        parent::setUp();

        $accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $accountId,
            'email' => 'biuro@mkl.pl',
            'owner' => 2,
            'active' => 1,
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->pracownik = Contact::create([
            'account_id' => $accountId,
            'first_name' => 'Szymon',
            'last_name' => 'Paśnikowski',
        ]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function formularze(): array
    {
        return [
            'nieobecności' => ['holiday/create'],
            'badania' => ['badania/create'],
            'BHP' => ['bhp/create'],
            'A1' => ['a1/create'],
            'języki' => ['jezyk/create'],
            'PBiOZ' => ['pbioz/create'],
            'uprawnienia' => ['uprawnienia/create'],
        ];
    }

    /**
     * @dataProvider formularze
     */
    public function test_formularz_zna_pracownika(string $sciezka): void
    {
        $odpowiedz = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik->id.'/'.$sciezka);

        $odpowiedz->assertOk();

        $pracownik = $odpowiedz->viewData('page')['props']['pracownik'];

        $this->assertSame('Paśnikowski Szymon', $pracownik['nazwa'], 'Brak nazwiska w '.$sciezka);
        $this->assertSame($this->pracownik->id, $pracownik['id']);
    }

    public function test_formularz_dokumentow_tez_zna_pracownika(): void
    {
        $odpowiedz = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik->id.'/documents/create');

        $odpowiedz->assertOk();

        $this->assertSame(
            'Paśnikowski Szymon',
            $odpowiedz->viewData('page')['props']['pracownik']['nazwa']
        );
    }
}
