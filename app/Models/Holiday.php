<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nieobecność pracownika (urlop, zwolnienie lekarskie itd.).
 * Powód pochodzi ze słownika shift_status — tego samego, co w kartach pracy.
 */
class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'shift_status_id',
        'start',
        'end',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function shiftStatus(): BelongsTo
    {
        return $this->belongsTo(ShiftStatus::class, 'shift_status_id');
    }

    /** Nieobecności obejmujące podany dzień. */
    public function scopeCoveringDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('start', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end')->orWhereDate('end', '>=', $date);
            });
    }

    /** Etykieta do pokazania na liście i karcie pracownika. */
    public function getLabelAttribute(): string
    {
        return $this->shiftStatus->title ?? 'Nieobecność';
    }
}
