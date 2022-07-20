<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KrajTyp extends Model
{
    use HasFactory;

    public function organization() {
        return $this->hasOne(Organization::class);
    }
}
