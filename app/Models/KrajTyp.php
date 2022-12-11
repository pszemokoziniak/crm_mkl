<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KrajTyp extends Model
{
    use HasFactory;

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }

    public function feasts(): HasMany
    {
        return $this->hasMany(Feast::class);
    }
}
