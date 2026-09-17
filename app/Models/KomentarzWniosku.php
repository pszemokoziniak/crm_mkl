<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Wpis w rozmowie przy wniosku urlopowym. Bez user_id = pracownik z telefonu. */
class KomentarzWniosku extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'wnioski_komentarze';

    protected $casts = ['created_at' => 'datetime'];

    public function wniosek(): BelongsTo
    {
        return $this->belongsTo(WniosekUrlopowy::class, 'wniosek_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function odPracownika(): bool
    {
        return $this->user_id === null;
    }
}
