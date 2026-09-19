<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Wpis kosztu podróży albo noclegu. Przypięty do pracownika, do budowy
 * albo do obu (bilet konkretnej osoby na konkretną budowę). Kwota
 * w walucie, przeliczona po kursie NBP z dnia kosztu i zamrożona w PLN.
 */
class Koszt extends Model
{
    use SoftDeletes;

    protected $table = 'koszty';

    protected $fillable = [
        'typ_kosztu_id', 'organization_id', 'contact_id', 'data', 'kwota', 'waluta',
        'kurs', 'kurs_reczny', 'kwota_pln', 'opis', 'od', 'do', 'miejsc', 'dzielony',
        'plik_sciezka', 'plik_nazwa', 'user_id',
    ];

    protected $casts = [
        'data' => 'date:Y-m-d',
        'od' => 'date:Y-m-d',
        'do' => 'date:Y-m-d',
        'kwota' => 'float',
        'kurs' => 'float',
        'kwota_pln' => 'float',
        'kurs_reczny' => 'bool',
        'dzielony' => 'bool',
        'miejsc' => 'int',
    ];

    public function typ(): BelongsTo
    {
        return $this->belongsTo(TypKosztu::class, 'typ_kosztu_id')->withTrashed();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class)->withTrashed();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function osoby(): HasMany
    {
        return $this->hasMany(KosztOsoba::class)->orderBy('od')->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jestNoclegiem(): bool
    {
        return (bool) optional($this->typ)->nocleg;
    }

    /** Koszty z datą w danym miesiącu (YYYY-MM). */
    public function scopeWMiesiacu(Builder $query, string $miesiac): Builder
    {
        [$od, $do] = self::zakresMiesiaca($miesiac);

        return $query->whereBetween('data', [$od, $do]);
    }

    /** @return array{0: string, 1: string} pierwszy i ostatni dzień miesiąca */
    public static function zakresMiesiaca(string $miesiac): array
    {
        $start = \Carbon\Carbon::createFromFormat('Y-m', $miesiac)->startOfMonth();

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }
}
