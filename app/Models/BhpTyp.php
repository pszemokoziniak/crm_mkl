<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BhpTyp extends Model
{
    use HasFactory;

    public function bhp() {
        return $this->hasOne(Bhp::class);
    }
}
