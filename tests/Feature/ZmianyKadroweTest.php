<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZmianaKadrowa;
use App\Notifications\ZmianaKadrowaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Rejestr zmian pobytów dla kadr — powstaje sam ze zmian w grafiku.
 */
class ZmianyKadroweTest extends TestCase
{
    use RefreshDatabase;

    private User $montaz;
    private User $kadry;
    private int $accountId;
    private Organization $budowaA;
    private Organization $budowaB;
    private Contact $pracownik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;
        $this->montaz = $this->user('montaz@example.com');
        $this->kadry = $this->user('kadry@example.com');

        $this->budowaA = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => 'Budowa A']);
        $this->budowaB = Organization::create(['account_id' => 0, 'name' => 'Vyncke', 'nazwaBud' => 'Budowa B']);

        $funkcja = Funkcja::create(['name' => 'Monter konstrukcji stalowych', 'kierownictwo' => false]);
        $this->pracownik = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'funkcja_id' => $funkcja->id,
        ]);
    }

    public function test_skrocenie_i_nowy_pobyt_to_jedno_przeniesienie(): void
    {
        $this->actingAs($this->montaz);

        $pobyt = $this->pobyt($this->budowaA, '2026-09-01', '2026-09-30');
        ZmianaKadrowa::query()->delete(); // wpis o nowym przypisaniu nas tu nie interesuje

        // Skracamy pobyt na A i dodajemy pobyt na B — dokładnie scenariusz z życia.
        $pobyt->update(['end' => '2026-09-14']);
        $this->pobyt($this->budowaB, '2026-09-15', '2026-10-31');

        $this->assertSame(1, ZmianaKadrowa::count());

        $zmiana = ZmianaKadrowa::first();
        $this->assertSame(ZmianaKadrowa::TYP_PRZENIESIENIE, $zmiana->typ);
        $this->assertSame($this->budowaA->id, $zmiana->organization_from_id);
        $this->assertSame($this->budowaB->id, $zmiana->organization_to_id);
        $this->assertSame('2026-09-15', $zmiana->new_start->format('Y-m-d'));
    }

    public function test_przeniesienie_ekipy_laduje_w_jednej_paczce(): void
    {
        $this->actingAs($this->montaz);

        foreach (range(1, 3) as $i) {
            $pracownik = Contact::create([
                'account_id' => $this->accountId,
                'first_name' => 'Pracownik'.$i,
                'last_name' => 'Nowak'.$i,
            ]);

            $pobyt = ContactWorkDate::create([
                'contact_id' => $pracownik->id,
                'organization_id' => $this->budowaA->id,
                'start' => '2026-09-01',
                'end' => '2026-09-30',
            ]);

            $pobyt->update(['end' => '2026-09-14']);

            ContactWorkDate::create([
                'contact_id' => $pracownik->id,
                'organization_id' => $this->budowaB->id,
                'start' => '2026-09-15',
                'end' => '2026-10-31',
            ]);
        }

        // Jedna paczka na całą serię, mimo trzech osób i dziewięciu operacji.
        $this->assertSame(1, ZmianaKadrowa::distinct()->count('paczka'));
        $this->assertSame(3, ZmianaKadrowa::where('typ', ZmianaKadrowa::TYP_PRZENIESIENIE)->count());
    }

    public function test_kadry_dostaja_jedno_powiadomienie_na_paczke(): void
    {
        Notification::fake();
        $this->actingAs($this->montaz);

        foreach (range(1, 3) as $i) {
            $pracownik = Contact::create([
                'account_id' => $this->accountId,
                'first_name' => 'Pracownik'.$i,
                'last_name' => 'Kowal'.$i,
            ]);

            ContactWorkDate::create([
                'contact_id' => $pracownik->id,
                'organization_id' => $this->budowaB->id,
                'start' => '2026-09-15',
                'end' => '2026-10-31',
            ]);
        }

        Notification::assertSentToTimes($this->kadry, ZmianaKadrowaNotification::class, 1);
        // Autor zmiany nie dostaje powiadomienia o własnej robocie.
        Notification::assertNotSentTo($this->montaz, ZmianaKadrowaNotification::class);
    }

    public function test_skrzynka_grupuje_i_opisuje_paczke(): void
    {
        $pobyt = $this->pobyt($this->budowaA, '2026-09-01', '2026-09-30');

        $this->actingAs($this->montaz);
        $pobyt->update(['end' => '2026-09-14']);
        $this->pobyt($this->budowaB, '2026-09-15', '2026-10-31');

        $this->actingAs($this->kadry)
            ->get('/zmiany-kadrowe')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ZmianyKadrowe/Index')
                ->has('paczki', 1)
                ->where('paczki.0.osob', 1)
                ->where('paczki.0.naglowek', 'Przeniesienie — Kowalski Jan: Budowa A → Budowa B')
                ->where('licznik', 1)
            );
    }

    public function test_kadry_zamykaja_cala_paczke_jednym_ruchem(): void
    {
        $this->actingAs($this->montaz);

        foreach (range(1, 4) as $i) {
            $pracownik = Contact::create([
                'account_id' => $this->accountId,
                'first_name' => 'Pracownik'.$i,
                'last_name' => 'Zieliński'.$i,
            ]);

            ContactWorkDate::create([
                'contact_id' => $pracownik->id,
                'organization_id' => $this->budowaB->id,
                'start' => '2026-09-15',
                'end' => '2026-10-31',
            ]);
        }

        $paczka = ZmianaKadrowa::first()->paczka;

        $this->actingAs($this->kadry)
            ->put('/zmiany-kadrowe', ['paczka' => $paczka, 'status' => ZmianaKadrowa::STATUS_GOTOWA])
            ->assertRedirect();

        $this->assertSame(0, ZmianaKadrowa::nieobsluzone()->count());
        $this->assertSame($this->kadry->id, ZmianaKadrowa::first()->handled_by);
    }

    public function test_zdjecie_z_budowy_tez_trafia_do_rejestru(): void
    {
        $this->actingAs($this->montaz);
        $pobyt = $this->pobyt($this->budowaA, '2026-09-01', '2026-09-30');
        ZmianaKadrowa::query()->delete();

        $pobyt->delete();

        $zmiana = ZmianaKadrowa::first();
        $this->assertSame(ZmianaKadrowa::TYP_USUNIECIE, $zmiana->typ);
        $this->assertSame($this->budowaA->id, $zmiana->organization_from_id);
    }

    public function test_dashboard_pokazuje_zmiany_dla_biura(): void
    {
        $this->actingAs($this->montaz);
        $this->pobyt($this->budowaB, '2026-09-15', '2026-10-31');

        $this->actingAs($this->kadry)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('zmiany_kadrowe', 1)
                ->where('zmiany_kadrowe_licznik', 1)
            );
    }

    public function test_zbiorcze_skrocenie_ustawia_date_wszystkim_zaznaczonym(): void
    {
        $this->actingAs($this->montaz);

        $pobyty = collect(range(1, 3))->map(function ($i) {
            $pracownik = Contact::create([
                'account_id' => $this->accountId,
                'first_name' => 'Pracownik'.$i,
                'last_name' => 'Skrocony'.$i,
            ]);

            return ContactWorkDate::create([
                'contact_id' => $pracownik->id,
                'organization_id' => $this->budowaA->id,
                'start' => '2026-09-01',
                'end' => '2026-09-30',
            ]);
        });

        ZmianaKadrowa::query()->delete();

        $this->put('/pracownicy/'.$this->budowaA->id.'/data-konca', [
            'ids' => $pobyty->pluck('id')->all(),
            'end' => '2026-09-14',
        ])->assertRedirect();

        foreach ($pobyty as $pobyt) {
            $this->assertSame('2026-09-14', $pobyt->fresh()->end);
        }

        // Skrócenia trafiają do rejestru tak samo jak przy poprawianiu pojedynczo.
        $this->assertSame(3, ZmianaKadrowa::where('typ', ZmianaKadrowa::TYP_SKROCENIE)->count());
        $this->assertSame(1, ZmianaKadrowa::distinct()->count('paczka'));
    }

    public function test_zbiorcze_skrocenie_nie_rusza_pobytow_z_innej_budowy(): void
    {
        $this->actingAs($this->montaz);

        $naA = $this->pobyt($this->budowaA, '2026-09-01', '2026-09-30');
        $innyPracownik = Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Obcy',
            'last_name' => 'Pracownik',
        ]);
        $naB = ContactWorkDate::create([
            'contact_id' => $innyPracownik->id,
            'organization_id' => $this->budowaB->id,
            'start' => '2026-09-01',
            'end' => '2026-09-30',
        ]);

        $this->put('/pracownicy/'.$this->budowaA->id.'/data-konca', [
            'ids' => [$naA->id, $naB->id],
            'end' => '2026-09-14',
        ])->assertRedirect();

        $this->assertSame('2026-09-14', $naA->fresh()->end);
        // Pobyt z budowy B nie mógł zostać ruszony przez akcję budowy A.
        $this->assertSame('2026-09-30', $naB->fresh()->end);
    }

    public function test_data_konca_przed_poczatkiem_jest_odrzucana(): void
    {
        $this->actingAs($this->montaz);
        $pobyt = $this->pobyt($this->budowaA, '2026-09-01', '2026-09-30');

        $this->put('/pracownicy/'.$this->budowaA->id.'/data-konca', [
            'ids' => [$pobyt->id],
            'end' => '2026-08-15',
        ])->assertRedirect();

        $this->assertSame('2026-09-30', $pobyt->fresh()->end);
        $this->assertStringContainsString('wcześniejsza niż początek', session('error'));
    }

    public function test_wpis_niesie_link_do_aneksu_z_danymi_przeniesienia(): void
    {
        $pobyt = $this->pobyt($this->budowaA, '2026-09-01', '2026-09-30');

        $this->actingAs($this->montaz);
        $pobyt->update(['end' => '2026-09-14']);
        $this->pobyt($this->budowaB, '2026-09-15', '2026-10-31');

        $this->actingAs($this->kadry)
            ->get('/zmiany-kadrowe')
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $link = $page->toArray()['props']['paczki'][0]['zmiany'][0]['link_aneks'];

                // Kadry nie przepisują ręcznie budowy ani terminu.
                $this->assertStringContainsString('/contacts/'.$this->pracownik->id.'/umowa', $link);
                $this->assertStringContainsString('rodzaj=aneks', $link);
                $this->assertStringContainsString('od=2026-09-15', $link);
                $this->assertStringContainsString('do=2026-10-31', $link);
                $this->assertStringContainsString(urlencode('Budowa B'), $link);
            });
    }

    public function test_formularz_umowy_przyjmuje_dane_z_adresu(): void
    {
        $this->actingAs($this->kadry)
            ->get('/contacts/'.$this->pracownik->id.'/umowa?rodzaj=aneks&budowa=Budowa+B&od=2026-09-15&do=2026-10-31')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Umowy/Formularz')
                ->where('domyslne.rodzaj', 'aneks')
                ->where('domyslne.budowa', 'Budowa B')
                ->where('domyslne.od', '2026-09-15')
                ->where('domyslne.do', '2026-10-31')
            );
    }

    private function user(string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => 2,
            'active' => 1,
            // Bez daty zmiany hasła middleware odsyła z pulpitu na ekran zmiany hasła.
            'password_changed_at' => now(),
        ]);
    }

    private function pobyt(Organization $budowa, string $start, string $end): ContactWorkDate
    {
        return ContactWorkDate::create([
            'contact_id' => $this->pracownik->id,
            'organization_id' => $budowa->id,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
