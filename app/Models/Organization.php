<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Organization extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function kierownik()
    {
        return $this->hasMany(Contact::class, 'id', 'kierownikBud_id');
    }

    public function contactworkdate()
    {
        return $this->hasMany(ContactWorkDate::class, 'organization_id', 'id');
    }

    public function krajTyp() {
        return $this->belongsTo(KrajTyp::class, 'country_id','id');
    }
    public function contactTypName() {
        return $this->belongsTo(Contact::class, 'kierownikBud_id','id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('nazwaBud', 'like', '%'.$search.'%')
                ->orWhere('numerBud', 'like', '%'.$search.'%')
                ->orWhereHas('krajTyp', function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%');
                });
//                ->orWhere('country', 'like', '%'.$search.'%');
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            } elseif ($trashed === 'my') {
                $query->where('kierownikBud_id', Auth::id());
            }
        });
    }
}
