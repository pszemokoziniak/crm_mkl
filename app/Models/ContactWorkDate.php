<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactWorkDate extends Model
{
    use HasFactory;

    public function scopeOrderByName($query)
    {
        $query->orderBy('start');
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }


    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('start', 'like', '%'.$search.'%')
                    ->orWhere('end', 'like', '%'.$search.'%')
                    ->orWhereHas('contact', function ($query) use ($search) {
                        $query->where('last_name', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('contact.funkcja', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }

}
