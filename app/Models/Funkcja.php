<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funkcja extends Model
{
    public const KIEROWNIK = 1;
    public const INZYNIER = 6;

    protected $fillable = [
        'name',
        'kierownictwo',
    ];

    protected $casts = [
        'kierownictwo' => 'boolean',
    ];

    public function contact()
    {
        return $this->hasMany(Contact::class);
    }

    public function funkcjas()
    {
        return $funkcjas = Funkcja::all();
    }

    /**
     * Stanowiska, które mogą wejść do kierownictwa budowy.
     * Lista jest w bazie (znacznik przy funkcji), nie w kodzie — biuro
     * zmienia ją samo w Ustawieniach.
     *
     * @return int[]
     */
    public static function kierownictwoIds(): array
    {
        return static::where('kierownictwo', true)->pluck('id')->all();
    }
}
