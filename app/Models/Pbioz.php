<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pbioz extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** Wiazanie trasy musi widziec kosz — bez tego /restore dawalo 404. */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('end')->orderBy('start');
    }
}
