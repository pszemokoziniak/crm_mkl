<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolWorkDate extends Model
{
    public function narzedzia()
    {
        return $this->belongsTo(Narzedzia::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereHas('narzedzia', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('numer_seryjny', 'like', '%'.$search.'%');
            });
        });
    }
}
