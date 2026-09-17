<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Kto dziś prowadzi budowy: kierownictwo z aktywnym pobytem (kierownik,
 * inżynier) plus kierownik projektu z pola przy budowie. Jedno miejsce
 * dla przypomnień, dzwonków i zatwierdzania wniosków.
 */
class KierownicyBudowy
{
    /**
     * @param int[] $orgIds
     * @return array<int, int[]> user_id => budowy
     */
    public function dlaBudow(array $orgIds, ?string $dzis = null): array
    {
        $dzis = $dzis ?? now()->toDateString();
        $wynik = [];

        $pobyty = ContactWorkDate::with('contact')
            ->whereIn('organization_id', $orgIds)
            ->activeOn($dzis)
            ->whereHas('contact', fn ($q) => $q->whereIn('funkcja_id', Funkcja::idsKierownictwaBudowy())->whereNotNull('user_id'))
            ->get();
        foreach ($pobyty as $p) {
            $wynik[(int) $p->contact->user_id][] = (int) $p->organization_id;
        }

        foreach (Organization::whereIn('id', $orgIds)->whereNotNull('kierownik_projektu_id')->get(['id', 'kierownik_projektu_id']) as $o) {
            $userId = Contact::withTrashed()->where('id', $o->kierownik_projektu_id)->value('user_id');
            if ($userId) {
                $wynik[(int) $userId][] = (int) $o->id;
            }
        }

        return array_map(fn ($lista) => array_values(array_unique($lista)), $wynik);
    }

    /**
     * Aktywne konta prowadzących podane budowy.
     *
     * @param int[] $orgIds
     * @return Collection<int, User>
     */
    public function uzytkownicy(array $orgIds): Collection
    {
        $ids = array_keys($this->dlaBudow($orgIds));

        return $ids ? User::whereIn('id', $ids)->where('active', true)->whereNull('deleted_at')->get() : collect();
    }
}
