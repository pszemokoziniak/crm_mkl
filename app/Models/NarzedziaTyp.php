<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NarzedziaTyp extends Model
{
    use HasFactory;

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
