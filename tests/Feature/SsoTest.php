<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\Sso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * SSO-lite: podpisany token niosący e-mail, jednorazowy i wygasający;
 * wejście loguje istniejącego, aktywnego użytkownika, a obce/nieaktywne blokuje.
 */
class SsoTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.sso.secret', 'tajny-testowy-sekret');
        config()->set('services.sso.crm_url', 'https://crm.example.test');
        $this->accountId = Account::create(['name' => 'MKL'])->id;
    }

    private function user(string $email, bool $active = true): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => $email, 'owner' => 2,
            'active' => $active, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_token_przechodzi_tam_i_z_powrotem_ale_tylko_raz(): void
    {
        $sso = app(Sso::class);
        $token = $sso->podpisz('jan@mkl.pl');

        $this->assertSame('jan@mkl.pl', $sso->odczytaj($token));
        // Drugi raz ten sam token już nie — jednorazowość.
        $this->assertNull($sso->odczytaj($token));
    }

    public function test_podrobiony_i_wygasly_token_odpada(): void
    {
        $sso = app(Sso::class);

        $this->assertNull($sso->odczytaj('cokolwiek.bezpodpisu'));

        // Podmiana ładunku bez znajomości sekretu psuje podpis.
        $token = $sso->podpisz('jan@mkl.pl');
        [$czesc, $sig] = explode('.', $token);
        $inny = rtrim(strtr(base64_encode(json_encode(['email' => 'wlamywacz@x.pl', 'exp' => time() + 60, 'jti' => 'x'])), '+/', '-_'), '=');
        $this->assertNull($sso->odczytaj($inny.'.'.$sig));
    }

    public function test_wejscie_loguje_istniejacego_uzytkownika(): void
    {
        $this->user('ala@mkl.pl');
        $token = app(Sso::class)->podpisz('ala@mkl.pl');

        $this->get('/sso/wejscie?token='.urlencode($token))->assertRedirect('/');
        $this->assertTrue(auth()->check());
        $this->assertSame('ala@mkl.pl', auth()->user()->email);
    }

    public function test_wejscie_blokuje_obcy_email_i_nieaktywnego(): void
    {
        $token = app(Sso::class)->podpisz('nie-ma@mkl.pl');
        $this->get('/sso/wejscie?token='.urlencode($token))->assertRedirect('/login');
        $this->assertFalse(auth()->check());

        $this->user('spioch@mkl.pl', active: false);
        Cache::flush();
        $token2 = app(Sso::class)->podpisz('spioch@mkl.pl');
        $this->get('/sso/wejscie?token='.urlencode($token2))->assertRedirect('/login');
        $this->assertFalse(auth()->check());
    }

    public function test_przejscie_do_crm_przekierowuje_z_tokenem(): void
    {
        $u = $this->user('kier@mkl.pl');

        $res = $this->actingAs($u)->get('/sso/do-crm');
        $res->assertredirect();
        $this->assertStringStartsWith('https://crm.example.test/sso/wejscie?token=', $res->headers->get('Location'));
    }
}
