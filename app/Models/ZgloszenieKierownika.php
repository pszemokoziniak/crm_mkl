<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zgłoszenie od kierownika budowy do kadr: zjazd, urlop, przeniesienie.
 * Samo niczego nie zmienia — kadry robią zmianę i zamykają zgłoszenie.
 */
class ZgloszenieKierownika extends Model
{
    protected $table = 'zgloszenia_kierownikow';

    public const RODZAJ_ZJAZD = 'zjazd';
    public const RODZAJ_URLOP = 'urlop';
    public const RODZAJ_PRZENIESIENIE = 'przeniesienie';
    public const RODZAJ_DOKUMENT = 'dokument';
    public const RODZAJ_INNE = 'inne';

    public const RODZAJE = [
        self::RODZAJ_ZJAZD => 'Zjazd z budowy',
        self::RODZAJ_URLOP => 'Urlop / nieobecność',
        self::RODZAJ_PRZENIESIENIE => 'Przeniesienie na inną budowę',
        self::RODZAJ_DOKUMENT => 'Brak dokumentu pracownika',
        self::RODZAJ_INNE => 'Inne',
    ];

    /** Czego brakuje — klucz to zakładka na karcie pracownika, gdzie kadry go dodają. */
    public const DOKUMENTY = [
        'a1' => 'A1',
        'badania' => 'Badania lekarskie',
        'uprawnienia' => 'Uprawnienia',
        'bhp' => 'Szkolenie BHP',
        'pbioz' => 'Certyfikat KJ',
        'inne' => 'Inny dokument',
    ];

    public const STATUS_NOWE = 'nowe';
    public const STATUS_OBSLUZONE = 'obsluzone';
    public const STATUS_ODRZUCONE = 'odrzucone';

    public const STATUSY = [
        self::STATUS_NOWE => 'czeka na kadry',
        self::STATUS_OBSLUZONE => 'obsłużone',
        self::STATUS_ODRZUCONE => 'odrzucone',
    ];

    protected $casts = [
        'od' => 'date:Y-m-d',
        'do' => 'date:Y-m-d',
        'obsluzone_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class)->withTrashed();
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /** Zgłoszenie powstałe z wniosku pracownika zatwierdzonego przez kierownika. */
    public function wniosek(): BelongsTo
    {
        return $this->belongsTo(WniosekUrlopowy::class, 'wniosek_id');
    }

    public function obsluzyl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'obsluzyl_id')->withTrashed();
    }

    public function scopeOtwarte(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NOWE);
    }

    public function rodzajLabel(): string
    {
        if ($this->rodzaj === self::RODZAJ_DOKUMENT) {
            return 'Brak dokumentu: '.$this->dokumentLabel();
        }

        return self::RODZAJE[$this->rodzaj] ?? $this->rodzaj;
    }

    public function dokumentLabel(): string
    {
        return self::DOKUMENTY[$this->dokument] ?? ($this->dokument ?: '—');
    }

    /** Gdzie kadry dodają brakujący dokument — zakładka na karcie pracownika. */
    public function adresDodaniaDokumentu(): ?string
    {
        if ($this->rodzaj !== self::RODZAJ_DOKUMENT) {
            return null;
        }

        $zakladka = in_array($this->dokument, ['a1', 'badania', 'uprawnienia', 'bhp', 'pbioz'], true)
            ? $this->dokument
            : 'documents';

        return '/contacts/'.$this->contact_id.'/'.$zakladka.'/create';
    }

    public function statusLabel(): string
    {
        return self::STATUSY[$this->status] ?? $this->status;
    }

    public function jestOtwarte(): bool
    {
        return $this->status === self::STATUS_NOWE;
    }
}
