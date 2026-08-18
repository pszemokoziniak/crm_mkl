<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** Maksymalna liczba pracowników na osi wykresu Prognozy. */
    public const PROGNOZA_MAX_WORKERS = 'prognoza_max_workers';

    protected $fillable = ['key', 'value'];

    /** Odczyt wartości ustawienia z fallbackiem, gdy klucza jeszcze nie zapisano. */
    public static function get(string $key, $default = null)
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    /** Zapis (lub aktualizacja) pojedynczego ustawienia. */
    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
