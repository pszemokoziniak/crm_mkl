<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funkcja extends Model
{
    public const KIEROWNIK = 1;
    public const INZYNIER = 6;

    public function contact()
    {
        return $this->hasMany(Contact::class);
    }

    public function funkcjas()
    {
        return $funkcjas = Funkcja::all();
    }

}
