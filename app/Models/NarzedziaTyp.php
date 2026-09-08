<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NarzedziaTyp extends Model
{
    use HasFactory;

    /** Egzemplarze tego modelu — do liczników na ekranie grup. */
    public function narzedzias(): HasMany
    {
        return $this->hasMany(Narzedzia::class, 'narzedzia_typ_id');
    }

    protected $fillable = [
        'name',
        'kategoria',
    ];

    /** Kategorie już użyte — podpowiedzi przy typie, żeby nie mnożyć zapisów. */
    public static function kategorie(): array
    {
        return static::query()
            ->whereNotNull('kategoria')
            ->where('kategoria', '!=', '')
            ->distinct()
            ->orderBy('kategoria')
            ->pluck('kategoria')
            ->all();
    }
}
