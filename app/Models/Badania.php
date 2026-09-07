<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badania extends Model
{
    // use HasFactory;
    use SoftDeletes;

    /**
     * Bez tego przywracanie nie działało: domyślne wiązanie trasy pomija
     * rekordy z kosza, więc /badania/{id}/restore zwracało 404.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function badaniaTyp() {
        return $this->belongsTo(BadaniaTyp::class, 'badaniaTyp_id','id');
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('end')->orderBy('start');
    }

    public function scopeOrderByName1($query)
    {
        $query->orderBy('name');
    }
}
