<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A1 extends Model
{
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
