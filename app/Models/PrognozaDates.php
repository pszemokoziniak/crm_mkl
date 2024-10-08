<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrognozaDates extends Model
{
    use HasFactory;


    public function prognoza()
    {
        return $this->hasMany(Prognoza::class);
    }
}
