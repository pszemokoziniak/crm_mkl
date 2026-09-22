<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Podpisany handoff między HRM a CRM (SSO-lite). Zalogowany w jednej
 * aplikacji przechodzi do drugiej z krótkim, podpisanym tokenem niosącym
 * e-mail. Druga aplikacja sprawdza podpis i loguje swojego użytkownika po
 * e-mailu — uprawnienia są już jej własne. Wspólny sekret w .env obu
 * aplikacji (SSO_SECRET). Token żyje 2 minuty i działa tylko raz.
 */
class Sso
{
    private const WAZNOSC = 120;

    public function podpisz(string $email): string
    {
        $czesc = self::b64((string) json_encode([
            'email' => $email,
            'exp' => time() + self::WAZNOSC,
            'jti' => Str::random(24),
        ]));

        return $czesc.'.'.self::b64(hash_hmac('sha256', $czesc, $this->sekret(), true));
    }

    /** E-mail, gdy token ma poprawny podpis, nie wygasł i nie był użyty; inaczej null. */
    public function odczytaj(?string $token): ?string
    {
        if (! $token || ! str_contains($token, '.')) {
            return null;
        }

        [$czesc, $sig] = explode('.', $token, 2);

        if (! hash_equals(self::b64(hash_hmac('sha256', $czesc, $this->sekret(), true)), $sig)) {
            return null;
        }

        $dane = json_decode(self::b64d($czesc), true);

        if (! is_array($dane) || empty($dane['email']) || empty($dane['exp']) || empty($dane['jti'])) {
            return null;
        }

        if ((int) $dane['exp'] < time()) {
            return null;
        }

        // Jednorazowość: ten sam token drugi raz nie przejdzie.
        if (! Cache::add('sso:'.$dane['jti'], 1, self::WAZNOSC + 30)) {
            return null;
        }

        return (string) $dane['email'];
    }

    private function sekret(): string
    {
        $sekret = (string) config('services.sso.secret');
        abort_if($sekret === '', 500, 'SSO nie jest skonfigurowane (brak SSO_SECRET).');

        return $sekret;
    }

    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private static function b64d(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/'));
    }
}
