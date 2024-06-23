<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prognoza extends Model
{
    use HasFactory;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function prognozadates()
    {
        return $this->belongsTo(PrognozaDates::class, 'prognoza_dates_id', 'id');
    }
}
