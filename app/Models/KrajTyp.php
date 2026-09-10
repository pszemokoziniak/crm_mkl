<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KrajTyp extends Model
{
    use HasFactory;

    protected $casts = [
        'wymaga_a1' => 'boolean',
    ];

    /**
     * Kraje, w których A1 nie jest potrzebne — w praktyce kraj macierzysty.
     * Znacznik siedzi w słowniku (Ustawienia → Kraj), więc zmiana przepisów
     * albo nowy kraj nie wymaga wdrożenia.
     *
     * @return int[]
     */
    public static function idsBezA1(): array
    {
        return static::where('wymaga_a1', false)->pluck('id')->all();
    }

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }

    /** Kraje na listach wyboru pokazujemy alfabetycznie. */
    public function scopeOrderByName($query)
    {
        $query->orderBy('name');
    }

    public function feasts(): HasMany
    {
        return $this->hasMany(Feast::class, 'country_id');
    }
}
