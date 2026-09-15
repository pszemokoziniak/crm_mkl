<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KrajTyp extends Model
{
    use HasFactory;

    /** Rok podatkowy: Austria, Francja, Hiszpania, Luksemburg, Włochy. */
    public const SPOSOB_ROK = 'rok_kalendarzowy';

    /** Każde ruchome 12 miesięcy: Niemcy, Belgia, Dania, Holandia i reszta. */
    public const SPOSOB_12M = 'dwanascie_miesiecy';

    public const SPOSOBY_183 = [
        self::SPOSOB_12M => 'Każde 12 miesięcy (ruchome okno)',
        self::SPOSOB_ROK => 'Rok podatkowy (kalendarzowy)',
    ];

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
