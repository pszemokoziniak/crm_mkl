<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kurs średni NBP (tabela A) zapamiętany raz na walutę i dzień. */
class KursWaluty extends Model
{
    protected $table = 'kursy_walut';

    protected $fillable = ['waluta', 'data', 'kurs', 'zrodlo'];

    protected $casts = ['data' => 'date:Y-m-d', 'kurs' => 'float'];
}
