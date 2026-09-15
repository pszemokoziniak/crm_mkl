<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ślad po pobraniu KCP budowy przez kadry. Zapisujemy tylko pobrania za
 * miniony miesiąc, bo tylko one zamykają miesiąc kierownikowi budowy.
 */
class PobranieKcp extends Model
{
    protected $table = 'kcp_pobrania';

    protected $fillable = ['organization_id', 'okres', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function okres(Carbon $dzien): string
    {
        return $dzien->format('Y-m');
    }

    /** Czy KCP tej budowy za ten miesiąc jest już zamknięte. */
    public static function czyZamkniete(int $organizationId, Carbon $dzien): bool
    {
        return static::where('organization_id', $organizationId)
            ->where('okres', static::okres($dzien))
            ->exists();
    }

    /**
     * Zapisuje pobranie, jeśli zamyka miesiąc: pobiera je ktoś z kadr,
     * a miesiąc KCP już się skończył. Pierwsze pobranie jest wiążące.
     */
    public static function zapiszJesliZamyka(int $organizationId, Carbon $miesiacKcp, ?User $kto): void
    {
        if (! $kto || ! $kto->hasRole(\App\Enums\Role::KADRY)) {
            return;
        }

        if ($miesiacKcp->copy()->startOfMonth()->gte(Carbon::today()->startOfMonth())) {
            return;
        }

        static::firstOrCreate(
            ['organization_id' => $organizationId, 'okres' => static::okres($miesiacKcp)],
            ['user_id' => $kto->id]
        );
    }
}
