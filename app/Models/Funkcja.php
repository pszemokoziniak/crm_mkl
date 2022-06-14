<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funkcja extends Model
{
    public function contact()
    {
        return $this->hasMany(Contact::class);
    }
    // use HasFactory;

    // protected $fillable = ['name'];
    // public function users()
    // {
    //     return $this->hasMany(User::class);
    // }

    // public function funkcjas()
    // {
    //     return $funkcjas = Funkcja::all();
    // }

    // public function contacts()
    // {
    //     return $this->hasMany(Contact::class);
    // }

    public function funkcjas()
    {
        return $funkcjas = Funkcja::all();
    }

}
