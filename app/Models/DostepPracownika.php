<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Osobisty link pracownika do strony na telefon. Link jest jak hasło:
 * w bazie tylko skrót, pełny adres widać raz przy wydaniu. Do tego PIN
 * ustawiany przez pracownika przy pierwszym wejściu — przesłany dalej
 * link bez PIN-u nic nie daje.
 */
class DostepPracownika extends Model
{
    protected $table = 'dostep_pracownika';

    public const MAX_PROB_PIN = 5;
    public const BLOKADA_MINUT = 15;

    protected $casts = [
        'zablokowany_do' => 'datetime',
        'wydany_at' => 'datetime',
        'wyslany_mail_at' => 'datetime',
        'wyslany_sms_at' => 'datetime',
        'ostatnie_wejscie_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function wydal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wydal_id')->withTrashed();
    }

    /**
     * Wydaje (albo wymienia) link: stary przestaje działać, PIN zostaje.
     *
     * @return string pełny token do adresu — jedyny moment, gdy go widać
     */
    public static function wydaj(Contact $contact, ?User $kto): string
    {
        $token = Str::random(48);

        $dostep = static::firstOrNew(['contact_id' => $contact->id]);
        $dostep->forceFill([
            'token_hash' => self::skrot($token),
            'wydal_id' => $kto?->id,
            'wydany_at' => now(),
            'wyslany_mail_at' => null,
            'wyslany_sms_at' => null,
            'proby_pin' => 0,
            'zablokowany_do' => null,
        ])->save();

        return $token;
    }

    public static function zTokenu(string $token): ?self
    {
        return static::where('token_hash', self::skrot($token))->first();
    }

    public static function skrot(string $token): string
    {
        return hash('sha256', $token);
    }

    public function maPin(): bool
    {
        return $this->pin_hash !== null;
    }

    public function ustawPin(string $pin): void
    {
        $this->forceFill(['pin_hash' => Hash::make($pin), 'proby_pin' => 0, 'zablokowany_do' => null])->save();
    }

    public function jestZablokowany(): bool
    {
        return $this->zablokowany_do !== null && $this->zablokowany_do->isFuture();
    }

    /** Sprawdza PIN; po pięciu chybieniach blokada na kwadrans. */
    public function sprawdzPin(string $pin): bool
    {
        if ($this->jestZablokowany()) {
            return false;
        }

        if ($this->pin_hash && Hash::check($pin, $this->pin_hash)) {
            $this->forceFill(['proby_pin' => 0, 'zablokowany_do' => null, 'ostatnie_wejscie_at' => now()])->save();

            return true;
        }

        $proby = $this->proby_pin + 1;
        $this->forceFill([
            'proby_pin' => $proby,
            'zablokowany_do' => $proby >= self::MAX_PROB_PIN ? now()->addMinutes(self::BLOKADA_MINUT) : null,
        ])->save();

        return false;
    }

    public function adres(string $token): string
    {
        return rtrim((string) config('app.url'), '/').'/u/'.$token;
    }
}
