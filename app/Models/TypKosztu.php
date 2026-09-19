<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Słownik "Typy kosztów" z Ustawień: bilet, paliwo, kwatera… */
class TypKosztu extends Model
{
    use SoftDeletes;

    protected $table = 'typy_kosztow';

    protected $fillable = ['nazwa', 'dzielony', 'nocleg'];

    protected $casts = ['dzielony' => 'bool', 'nocleg' => 'bool'];

    public function koszty(): HasMany
    {
        return $this->hasMany(Koszt::class, 'typ_kosztu_id');
    }
}
