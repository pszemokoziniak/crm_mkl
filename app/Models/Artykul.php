<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Artykuł bazy wiedzy. Treść pisze się w markdownie, bo instrukcje składają
 * się głównie z kroków i poleceń do skopiowania.
 */
class Artykul extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'baza_wiedzy';

    protected $casts = [
        'tylko_admin' => 'boolean',
    ];

    /** Artykuły techniczne (ścieżki na serwerze, polecenia) widzi tylko admin. */
    public function scopeWidoczneDla(Builder $query, ?User $user): Builder
    {
        if ($user && $user->isAdmin()) {
            return $query;
        }

        return $query->where('tylko_admin', false);
    }

    public function scopeUlozone(Builder $query): Builder
    {
        return $query->orderBy('kolejnosc')->orderBy('tytul');
    }

    public function scopeSzukaj(Builder $query, ?string $fraza): Builder
    {
        if (! $fraza) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($fraza) {
            $q->where('tytul', 'like', '%'.$fraza.'%')
                ->orWhere('kategoria', 'like', '%'.$fraza.'%')
                ->orWhere('tresc', 'like', '%'.$fraza.'%');
        });
    }

    /**
     * Markdown → HTML. Wynik trafia do v-html, więc wycinamy surowy HTML
     * z treści — nawet jeśli dziś pisać może tylko admin.
     */
    public function html(): string
    {
        return (string) Str::markdown($this->tresc ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /** Kilka pierwszych zdań na listę — bez znaczników markdowna. */
    public function zajawka(int $znakow = 160): string
    {
        $czysty = trim(preg_replace('/\s+/u', ' ', strip_tags($this->html())));

        return Str::limit($czysty, $znakow);
    }
}
