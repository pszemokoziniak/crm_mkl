<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * Jedno wejście do systemu — udane albo nie.
 */
class Logowanie extends Model
{
    protected $table = 'logowania';

    /** Zapisujemy tylko moment zdarzenia; nic tu się później nie zmienia. */
    public $timestamps = false;

    public const POWOD_BRAK_KONTA = 'nie ma takiego konta';
    public const POWOD_ZABLOKOWANE = 'konto zablokowane';
    public const POWOD_ZLE_HASLO = 'błędne hasło';

    protected $casts = [
        'udane' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public static function zapisz(Request $request, ?User $user, bool $udane, ?string $powod = null): void
    {
        static::create([
            'user_id' => optional($user)->id,
            'email' => mb_substr((string) $request->input('email'), 0, 150),
            'udane' => $udane,
            'powod' => $powod,
            'ip' => $request->ip(),
            'przegladarka' => mb_substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
        ]);
    }

    public function scopeFilter($query, array $filtry)
    {
        $query->when($filtry['szukaj'] ?? null, function ($query, $szukaj) {
            $query->where(function ($q) use ($szukaj) {
                $q->where('email', 'like', '%'.$szukaj.'%')
                    ->orWhere('ip', 'like', '%'.$szukaj.'%')
                    ->orWhereHas('user', function ($u) use ($szukaj) {
                        $u->where('first_name', 'like', '%'.$szukaj.'%')
                            ->orWhere('last_name', 'like', '%'.$szukaj.'%');
                    });
            });
        })->when($filtry['wynik'] ?? null, function ($query, $wynik) {
            if ($wynik === 'nieudane') {
                $query->where('udane', false);
            } elseif ($wynik === 'udane') {
                $query->where('udane', true);
            }
        })->when($filtry['od'] ?? null, fn ($query, $od) => $query->whereDate('created_at', '>=', $od))
            ->when($filtry['do'] ?? null, fn ($query, $do) => $query->whereDate('created_at', '<=', $do));
    }
}
