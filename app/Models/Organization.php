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

    public function contactWorkDates()
    {
        return $this->hasMany(ContactWorkDate::class, 'organization_id', 'id');
    }

    public function inzynier()
    {
        return $this->belongsTo(Contact::class, 'inzynier_id','id');
    }

    public function krajTyp()
    {
        return $this->belongsTo(KrajTyp::class, 'country_id','id');
    }
    public function kierownik()
    {
        return $this->belongsTo(Contact::class, 'kierownikBud_id','id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('organizations.name', 'like', '%'.$search.'%')
                    ->orWhere('organizations.nazwaBud', 'like', '%'.$search.'%')
                    ->orWhere('organizations.numerBud', 'like', '%'.$search.'%')
                    ->orWhereHas('krajTyp', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            } elseif ($trashed === 'my') {
                $contact = Contact::where('user_id', Auth::id())->first();
                $contact_id = $contact ? $contact->id : null;
                $query->where(function($q) use ($contact_id) {
                    $q->where('kierownikBud_id', $contact_id)
                      ->orWhere('inzynier_id', $contact_id);
                })->withTrashed();
            }
        }, function ($query) {
            $query->whereNull('organizations.deleted_at');
        });
    }
}
