<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zmiana pobytu pracownika na budowie, którą muszą obsłużyć kadry
 * (aneks do umowy). Wpisy powstają automatycznie z ContactWorkDateObserver.
 */
class ZmianaKadrowa extends Model
{
    protected $table = 'zmiany_kadrowe';

    public const TYP_NOWY = 'nowy';
    public const TYP_PRZENIESIENIE = 'przeniesienie';
    public const TYP_SKROCENIE = 'skrocenie';
    public const TYP_WYDLUZENIE = 'wydluzenie';
    public const TYP_ZMIANA_TERMINU = 'zmiana_terminu';
    public const TYP_USUNIECIE = 'usuniecie';

    public const STATUS_NOWA = 'nowa';
    public const STATUS_W_PRZYGOTOWANIU = 'w_przygotowaniu';
    public const STATUS_GOTOWA = 'gotowa';
    /** Sprawa zamknięta bez dokumentu — np. pracownik po prostu zjechał z budowy. */
    public const STATUS_BEZ_ANEKSU = 'bez_aneksu';

    /** Statusy, po których sprawa nie czeka już na kadry. */
    public const STATUSY_ZAMKNIETE = [self::STATUS_GOTOWA, self::STATUS_BEZ_ANEKSU];

    protected $fillable = [
        'contact_id',
        'typ',
        'organization_from_id',
        'organization_to_id',
        'old_start',
        'old_end',
        'new_start',
        'new_end',
        'paczka',
        'status',
        'changed_by',
        'handled_by',
        'handled_at',
        'uwagi',
        'mail_wyslany_at',
    ];

    protected $casts = [
        'old_start' => 'date:Y-m-d',
        'old_end' => 'date:Y-m-d',
        'new_start' => 'date:Y-m-d',
        'new_end' => 'date:Y-m-d',
        'handled_at' => 'datetime',
        'mail_wyslany_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        // withTrashed, żeby nazwisko nie znikało po przeniesieniu pracownika
        // do archiwum — kadry muszą wiedzieć, kogo dotyczył wpis.
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function budowaZ(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_from_id')->withTrashed();
    }

    public function budowaDo(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_to_id')->withTrashed();
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function obsluzylUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeNieobsluzone(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::STATUSY_ZAMKNIETE);
    }

    public function typLabel(): string
    {
        return [
            self::TYP_NOWY => 'Nowe przypisanie',
            self::TYP_PRZENIESIENIE => 'Przeniesienie',
            self::TYP_SKROCENIE => 'Skrócenie pobytu',
            self::TYP_WYDLUZENIE => 'Wydłużenie pobytu',
            self::TYP_ZMIANA_TERMINU => 'Zmiana terminu',
            self::TYP_USUNIECIE => 'Zdjęcie z budowy',
        ][$this->typ] ?? $this->typ;
    }

    public function statusLabel(): string
    {
        return [
            self::STATUS_NOWA => 'Nowa',
            self::STATUS_W_PRZYGOTOWANIU => 'W przygotowaniu',
            self::STATUS_GOTOWA => 'Umowa gotowa',
            self::STATUS_BEZ_ANEKSU => 'Zamknięta bez aneksu',
        ][$this->status] ?? $this->status;
    }
}
