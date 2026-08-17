<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ZadanieStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Zgłoszenie w procesie testowania strony.
 */
class Zadanie extends Model
{
    use SoftDeletes;

    protected $table = 'zadania';

    protected $fillable = [
        'title',
        'description',
        'url',
        'status',
        'priority',
        'reporter_id',
        'assignee_id',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'date:Y-m-d',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ZadanieFile::class)->orderBy('id');
    }

    /** Załączniki zgłoszenia (bez tych dodanych w komentarzach). */
    public function screenshots(): HasMany
    {
        return $this->files()->whereNull('note_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderBy('id');
    }

    public function statusLabel(): string
    {
        return ZadanieStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status;
    }

    /**
     * Zgłoszenia widoczne dla użytkownika: biuro i admin widzą wszystko,
     * pozostali tylko swoje (zgłoszone lub przypisane).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOffice()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('reporter_id', $user->id)
                ->orWhere('assignee_id', $user->id);
        });
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('url', 'like', '%'.$search.'%');
            });
        })->when($filters['status'] ?? null, function (Builder $query, string $status) {
            $query->where('status', $status);
        })->when($filters['priority'] ?? null, function (Builder $query, string $priority) {
            $query->where('priority', $priority);
        })->when($filters['assignee'] ?? null, function (Builder $query, $assignee) {
            $query->where('assignee_id', (int) $assignee);
        })->when($filters['trashed'] ?? null, function (Builder $query, string $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }
}
