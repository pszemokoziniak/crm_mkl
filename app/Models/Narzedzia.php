<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Narzedzia extends Model
{
    protected $fillable = [
        'name',
        'numer_seryjny',
        'waznosc_badan',
        'ilosc_all',
        'ilosc_budowa',
        'ilosc_magazyn',
    ];

    protected $casts = [
        'ilosc_all' => 'integer',
        'ilosc_budowa' => 'integer',
        'ilosc_magazyn' => 'integer',
        'waznosc_badan' => 'date:Y-m-d',
    ];

    /**
     * Accessor: Zawsze zwracaj obliczoną wartość, nawet jeśli w bazie jest błąd.
     * Dzięki temu w Vue zawsze zobaczysz poprawny wynik.
     */
    public function getIloscMagazynAttribute()
    {
        return ($this->ilosc_all ?? 0) - ($this->ilosc_budowa ?? 0);
    }

    protected static function booted()
    {
        static::saving(function ($narzedzia) {
            // Przy każdym zapisie aktualizujemy kolumnę w bazie
            $narzedzia->ilosc_magazyn = ($narzedzia->ilosc_all ?? 0) - ($narzedzia->ilosc_budowa ?? 0);
        });
    }

    public function files(): HasMany
    {
        return $this->hasMany(ToolFile::class, 'tool_id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('numer_seryjny', 'like', '%'.$search.'%');
            });
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            // SoftDeletes not implemented in migration yet
        });
    }
}
