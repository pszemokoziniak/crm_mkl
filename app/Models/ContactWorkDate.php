<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ContactWorkDate extends Model
{
    protected $table = 'contact_work_dates';

    protected $fillable = [
        'contact_id',
        'organization_id',
        'start',
        'end',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('last_name')->orderBy('first_name');
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

    public function scopeActiveOn(Builder $query, string $date): Builder
    {
        return $query
            ->whereDate('start', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end')->orWhereDate('end', '>=', $date);
            });
    }

    public function scopeNotFinished(Builder $query, string $date): Builder
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('end')->orWhereDate('end', '>=', $date);
        });
    }
}
