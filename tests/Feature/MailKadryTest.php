<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ZmianyKadroweMail;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZmianaKadrowa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * E-mail do kadr o zmianach czekających w zakładce. Jedna wiadomość
 * na paczkę i dopiero wtedy, gdy paczka przestanie rosnąć — inaczej
 * przeniesienie ekipy rozsypałoby się na kilka urywków.
 */
class MailKadryTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $montaz;
    private User $kadry;
    private Organization $budowaA;
    private Organization $budowaB;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->montaz = $this->user('montaz@mkl.pl', false);
        $this->kadry = $this->user('kadry@mkl.pl', true);

        $this->budowaA = Organization::create(['account_id' => 0, 'name' => 'Valmet', 'nazwaBud' => 'Budowa A']);
        $this->budowaB = Organization::create(['account_id' => 0, 'name' => 'Andritz', 'nazwaBud' => 'Budowa B']);
    }

    private function user(string $email, bool $powiadomienia): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId,
            'email' => $email,
            'owner' => 2,
            'active' => 1,
            'powiadomienia_kadrowe' => $powiadomienia,
            'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function pracownik(string $nazwisko): Contact
    {
        return Contact::create([
            'account_id' => $this->accountId,
            'first_name' => 'Jan',
            'last_name' => $nazwisko,
        ]);
    }

    /** Przeniesienie: skrócenie na A i nowy pobyt na B. */
    private function przenies(Contact $pracownik): void
    {
        $pobyt = ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $this->budowaA->id,
            'start' => '2026-09-01',
            'end' => '2026-09-30',
        ]);

        $this->actingAs($this->montaz);

        $pobyt->update(['end' => '2026-09-14']);

        ContactWorkDate::create([
            'contact_id' => $pracownik->id,
            'organization_id' => $this->budowaB->id,
            'start' => '2026-09-15',
            'end' => '2026-10-31',
        ]);
    }

    public function test_swieza_paczka_jeszcze_nie_idzie_mailem(): void
    {
        $this->przenies($this->pracownik('Kowalski'));

        $this->artisan('kadry:powiadom-mailem')->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertNull(ZmianaKadrowa::first()->mail_wyslany_at);
    }

    public function test_zamknieta_paczka_idzie_jednym_mailem(): void
    {
        $this->przenies($this->pracownik('Kowalski'));
        $this->przenies($this->pracownik('Nowak'));

        // Paczka ucichła — montaż skończył przenoszenie ekipy.
        ZmianaKadrowa::query()->update(['created_at' => now()->subHour()]);

        $this->artisan('kadry:powiadom-mailem')->assertExitCode(0);

        Mail::assertSent(ZmianyKadroweMail::class, 1);
        Mail::assertSent(ZmianyKadroweMail::class, fn ($mail) => $mail->hasTo('kadry@mkl.pl'));
        Mail::assertNotSent(ZmianyKadroweMail::class, fn ($mail) => $mail->hasTo('montaz@mkl.pl'));
    }

    public function test_ta_sama_paczka_nie_idzie_dwa_razy(): void
    {
        $this->przenies($this->pracownik('Kowalski'));
        ZmianaKadrowa::query()->update(['created_at' => now()->subHour()]);

        $this->artisan('kadry:powiadom-mailem');
        Mail::assertSent(ZmianyKadroweMail::class, 1);

        $this->artisan('kadry:powiadom-mailem');
        Mail::assertSent(ZmianyKadroweMail::class, 1);
    }

    public function test_bez_wskazanych_odbiorcow_nic_nie_leci(): void
    {
        $this->kadry->update(['powiadomienia_kadrowe' => false]);

        $this->przenies($this->pracownik('Kowalski'));
        ZmianaKadrowa::query()->update(['created_at' => now()->subHour()]);

        $this->artisan('kadry:powiadom-mailem')->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertNull(ZmianaKadrowa::first()->mail_wyslany_at);
    }

    public function test_zablokowane_konto_nie_dostaje_maila(): void
    {
        $this->kadry->update(['active' => 0]);

        $this->przenies($this->pracownik('Kowalski'));
        ZmianaKadrowa::query()->update(['created_at' => now()->subHour()]);

        $this->artisan('kadry:powiadom-mailem');

        Mail::assertNothingSent();
    }

    public function test_mail_niesie_nazwiska_i_budowy(): void
    {
        $this->przenies($this->pracownik('Kowalski'));
        ZmianaKadrowa::query()->update(['created_at' => now()->subHour()]);

        $this->artisan('kadry:powiadom-mailem');

        $wyslany = null;
        Mail::assertSent(ZmianyKadroweMail::class, function (ZmianyKadroweMail $mail) use (&$wyslany) {
            $wyslany = $mail;

            return true;
        });

        // Mail::fake() podmienia mailer, więc szablon renderujemy wprost.
        $tresc = view('emails.zmiany-kadrowe', [
            'zmiany' => $wyslany->zmiany,
            'autor' => $wyslany->autor,
            'adresZakladki' => $wyslany->adresZakladki,
        ])->render();

        $this->assertStringContainsString('Kowalski', $tresc);
        $this->assertStringContainsString('Budowa A', $tresc);
        $this->assertStringContainsString('Budowa B', $tresc);
        $this->assertStringContainsString('/zmiany-kadrowe', $tresc);
    }

    public function test_biuro_wlacza_powiadomienia_przy_koncie(): void
    {
        $nowy = $this->user('inny@mkl.pl', false);

        $this->actingAs($this->kadry)
            ->put('/users/'.$nowy->id, [
                'first_name' => $nowy->first_name,
                'last_name' => $nowy->last_name,
                'email' => $nowy->email,
                'powiadomienia_kadrowe' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($nowy->fresh()->powiadomienia_kadrowe);
    }
}
