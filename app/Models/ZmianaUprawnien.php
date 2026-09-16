<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Dziennik zmian macierzy: kto, kiedy, której roli co dodał i co odebrał. */
class ZmianaUprawnien extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'uprawnienia_zmiany';

    protected $casts = [
        'dodane' => 'array',
        'odebrane' => 'array',
        'przywrocenie' => 'boolean',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
