<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JezykTyp extends Model
{
    use HasFactory;

    public function jezyk() {
        return $this->hasOne(Jezyk::class);
    }
}
