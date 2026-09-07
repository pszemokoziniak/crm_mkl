<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class A1 extends Model
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

    public function scopeOrderByName($query)
    {
        $query->orderBy('end', 'desc');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function kraj()
    {
        return $this->belongsTo(KrajTyp::class, 'kraj_typs_id', 'id');
    }
}
