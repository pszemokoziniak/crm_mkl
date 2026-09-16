<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nadpisana lista uprawnień jednej roli — patrz App\Uprawnienia\Macierz. */
class UprawnieniaRoli extends Model
{
    protected $table = 'uprawnienia_rol';

    protected $casts = [
        'uprawnienia' => 'array',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
