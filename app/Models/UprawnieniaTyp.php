<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UprawnieniaTyp extends Model
{
    use HasFactory;

    public function uprawnienia() {
        return $this->hasOne(UprawnieniaTyp::class);
    }
}
