<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uprawnienia extends Model
{
    use HasFactory;


    public function uprawnieniaTyp() {
        return $this->belongsTo(UprawnieniaTyp::class, 'uprawnieniaTyp_id','id');
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('end')->orderBy('start');
    }
}
