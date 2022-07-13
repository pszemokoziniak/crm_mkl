<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jezyk extends Model
{
    use HasFactory;

    public function jezykTyp() {
        return $this->belongsTo(JezykTyp::class, 'jezykTyp_id','id');
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('jezykTyp_id');
    }
}
