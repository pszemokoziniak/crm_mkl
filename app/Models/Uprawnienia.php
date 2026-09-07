<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uprawnienia extends Model
{
    use SoftDeletes;

    /**
     * Wiazanie trasy musi widziec kosz — bez tego /restore dawalo 404.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }
    use HasFactory;


    public function uprawnieniaTyp() {
        return $this->belongsTo(UprawnieniaTyp::class, 'uprawnieniaTyp_id','id');
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('end')->orderBy('start');
    }
}
