<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactWorkDate extends Model
{
    use HasFactory;

    public function scopeOrderByName($query)
    {
        $query->orderBy('start');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
