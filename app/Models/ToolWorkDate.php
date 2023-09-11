<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolWorkDate extends Model
{
    use HasFactory;

    public function narzedzia()
    {
        return $this->belongsTo(Narzedzia::class);
    }
}
