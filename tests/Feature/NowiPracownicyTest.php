<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Models\Bhp;
use App\Models\BhpTyp;
use App\Models\Contact;
use App\Models\User;
use App\Services\NowiPracownicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kadry: nowo wprowadzony pracownik ma dostać badania i BHP (obowiązkowo),
 * uprawnienia opcjonalnie. Upominamy się do skompletowania; potem pilnuje
 * ich zwykły raport terminów.
 */
class NowiPracownicyTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
    }

    private function pracownik(string $nazwisko, int $dniTemu = 3, ?string $status = null): Contact
    {
        // Kolumna nie przyjmuje NULL — status podajemy tylko, gdy test go potrzebuje.
        $c = Contact::create(array_filter(['account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => $nazwisko, 'status_zatrudnienia' => $status]));
        Contact::where('id', $c->id)->update(['created_at' => now()->subDays($dniTemu)]);

        return $c->fresh();
    }

    private function badania(Contact $c, string $koniec): void
    {
        Badania::create(['contact_id' => $c->id, 'badaniaTyp_id' => BadaniaTyp::firstOrCreate(['name' => 'Wstępne'])->id, 'start' => now()->subYear()->toDateString(), 'end' => $koniec]);
    }

    private function bhp(Contact $c, string $koniec): void
    {
        Bhp::create(['contact_id' => $c->id, 'bhpTyp_id' => BhpTyp::firstOrCreate(['name' => 'Wstępne'])->id, 'start' => now()->subYear()->toDateString(), 'end' => $koniec]);
    }

    public function test_nowy_bez_badan_lub_bhp_jest_na_liscie_a_z_kompletem_znika(): void
    {
        $bezNiczego = $this->pracownik('Nowak');
        $tylkoBadania = $this->pracownik('Kowal');
        $this->badania($tylkoBadania, now()->addYear()->toDateString());
        $komplet = $this->pracownik('Pełny');
        $this->badania($komplet, now()->addYear()->toDateString());
        $this->bhp($komplet, now()->addYear()->toDateString());
        $przeterminowane = $this->pracownik('Stary');
        $this->badania($przeterminowane, now()->subDay()->toDateString());
        $this->bhp($przeterminowane, now()->addYear()->toDateString());

        $lista = app(NowiPracownicy::class)->bezKompletu();

        $this->assertEqualsCanonicalizing(['Nowak Jan', 'Kowal Jan', 'Stary Jan'], $lista->pluck('pracownik')->all());
        $kowal = $lista->firstWhere('id', $tylkoBadania->id);
        $this->assertTrue($kowal['badania']);
        $this->assertFalse($kowal['bhp']);
        $this->assertFalse($kowal['uprawnienia']);
        $this->assertSame(3, $kowal['dni_temu']);
    }

    public function test_dawno_wprowadzeni_i_zwolnieni_nie_sa_juz_nowi(): void
    {
        $this->pracownik('Dawny', 120);
        $this->pracownik('Zwolniony', 5, Contact::STATUS_ZWOLNIONY);
        $this->pracownik('Świeży', 89);

        $this->assertSame(['Świeży Jan'], app(NowiPracownicy::class)->bezKompletu()->pluck('pracownik')->all());
    }

    public function test_ekran_kadry_pokazuje_liste(): void
    {
        $this->pracownik('Nowak');
        $kadry = User::factory()->create(['account_id' => $this->accountId, 'email' => 'kadry@mkl.pl', 'owner' => 6, 'active' => 1, 'password_changed_at' => now()->toDateTimeString()]);

        $props = $this->actingAs($kadry)->get('/zmiany-kadrowe')->viewData('page')['props'];

        $this->assertCount(1, $props['nowi_pracownicy']);
        $this->assertSame('Nowak Jan', $props['nowi_pracownicy'][0]['pracownik']);
    }
}
