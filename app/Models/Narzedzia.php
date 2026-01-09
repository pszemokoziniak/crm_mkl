<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Narzedzia extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'numer_seryjny',
        'waznosc_badan',
        'ilosc_all',
        'ilosc_budowa',
        'ilosc_magazyn',
    ];

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
            /*
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
            */
        });
    }
}
