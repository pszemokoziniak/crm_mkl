<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BadaniaTyp extends Model
{
//    use HasFactory;

    public function badaniaTyps()
    {
        return $badaniaTyps = BadaniaTyp::all();
    }
}
