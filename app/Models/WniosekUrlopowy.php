<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Wniosek urlopowy złożony przez pracownika z telefonu. Zatwierdza
 * kierownik budowy albo kierownik projektu; zatwierdzony idzie do kadr
 * jako zgłoszenie urlopu (jak zgłoszenie od kierownika) i liczy się
 * jako wniosek — bez skanu.
 */
class WniosekUrlopowy extends Model
{
    protected $table = 'wnioski_urlopowe';

    /** Kody z KCP, o które pracownik może wnioskować. */
    public const RODZAJE = [
        'UW' => 'Urlop wypoczynkowy',
        'UŻ' => 'Urlop na żądanie',
        'UB' => 'Urlop bezpłatny',
    ];

    public const STATUS_ZLOZONY = 'zlozony';
    public const STATUS_ZATWIERDZONY = 'zatwierdzony';
    public const STATUS_ODRZUCONY = 'odrzucony';

    public const STATUSY = [
        self::STATUS_ZLOZONY => 'czeka na kierownika',
        self::STATUS_ZATWIERDZONY => 'zatwierdzony',
        self::STATUS_ODRZUCONY => 'odrzucony',
    ];

    protected $casts = [
        'od' => 'date:Y-m-d',
        'do' => 'date:Y-m-d',
        'rozpatrzony_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function komentarze(): HasMany
    {
        return $this->hasMany(KomentarzWniosku::class, 'wniosek_id')->orderBy('id');
    }

    public function rozpatrzyl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rozpatrzyl_id')->withTrashed();
    }

    public function scopeZlozone(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ZLOZONY);
    }

    public function rodzajLabel(): string
    {
        return self::RODZAJE[$this->rodzaj] ?? $this->rodzaj;
    }

    public function statusLabel(): string
    {
        return self::STATUSY[$this->status] ?? $this->status;
    }

    public function dni(): int
    {
        return (int) $this->od->diffInDays($this->do, true) + 1;
    }
}
