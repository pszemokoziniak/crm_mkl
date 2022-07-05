<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bhp extends Model
{
    use HasFactory;

    public function bhpTyp() {
        return $this->belongsTo(BhpTyp::class, 'bhpTyp_id','id');
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('end')->orderBy('start');
    }
}
