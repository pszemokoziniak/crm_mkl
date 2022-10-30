<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pbioz extends Model
{
    use HasFactory;

    public function scopeOrderByName($query)
    {
        $query->orderBy('end')->orderBy('start');
    }
}
