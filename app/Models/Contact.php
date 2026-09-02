<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AKTYWNY = 'Aktywny';
    public const STATUS_URLOP = 'Urlop';
    public const STATUS_ZWOLNIONY = 'Zwolniony';

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'pesel',
        'idCard_number',
        'idCard_date',
        'funkcja_id',
        'work_start',
        'work_end',
        'ekuz',
        'miejsce_urodzenia',
        'organization_id',
        'email',
        'phone',
        'address',
        'photo_path',
        'status_zatrudnienia',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function funkcja()
    {
        return $this->belongsTo(Funkcja::class);
    }

    public function a1()
    {
        return $this->hasMany(A1::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organizationKierownikname() {
        return $this->hasOne(Organization::class);
    }

    /** Pobyty na budowach. */
    public function workDates(): HasMany
    {
        return $this->hasMany(ContactWorkDate::class);
    }

    /** Nieobecności — urlopy, zwolnienia itd. */
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CtnDocument::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function scopeOrderByName($query)
    {
        // Kolacja polska — inaczej ż/ś/ł mieszają się z z/s/l (domyślna
        // utf8mb4_unicode_ci traktuje je jako równe podstawowej literze).
        $query->orderByRaw('last_name COLLATE utf8mb4_polish_ci asc')
            ->orderByRaw('first_name COLLATE utf8mb4_polish_ci asc');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhereHas('funkcja', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        })->when($filters['status'] ?? null, function ($query, $status) {
            // "Na budowie" = pobyt aktywny dziś (start <= dziś <= end), jak w
            // kolumnie "Pracuje na budowie". "Dostępni" = odwrotność.
            $today = now()->toDateString();
            $activeSub = function ($q) use ($today) {
                $q->select('contact_id')->from('contact_work_dates')
                    ->where('start', '<=', $today)
                    ->where('end', '>=', $today);
            };
            if ($status === 'na_budowie') {
                $query->whereIn('id', $activeSub);
            } elseif ($status === 'dostepni') {
                $query->whereNotIn('id', $activeSub);
            }
        });
    }
}
