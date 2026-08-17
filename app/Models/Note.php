<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Komentarz przypisany polimorficznie (na razie do zgłoszeń — Zadanie).
 * Treść może zawierać wzmianki w formacie @[Imię Nazwisko](user:ID).
 */
class Note extends Model
{
    protected $fillable = [
        'user_id',
        'notable_type',
        'notable_id',
        'body',
        'system',
    ];

    protected $casts = [
        'system' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Załączniki dodane razem z komentarzem. */
    public function files(): HasMany
    {
        return $this->hasMany(ZadanieFile::class, 'note_id')->orderBy('id');
    }
}
