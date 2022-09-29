<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumentyTyp extends Model
{
    use HasFactory;

    public function ctndocument()
    {
        return $this->hasMany(CtnDocument::class);
    }
}
