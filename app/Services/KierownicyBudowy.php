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
     * Kierownictwo budów jako osoby z telefonem — do kafelka "Mój kierownik"
     * na stronie pracownika. Konto w HRM nie jest tu potrzebne.
     *
     * @param int[] $orgIds
     * @return Collection<int, array{id: int, nazwa: string, stanowisko: ?string, telefon: ?string}>
     */
    public function osoby(array $orgIds, ?string $dzis = null): Collection
    {
        $dzis = $dzis ?? now()->toDateString();

        $kontakty = ContactWorkDate::with('contact.funkcja')
            ->whereIn('organization_id', $orgIds)
            ->activeOn($dzis)
            ->whereHas('contact', fn ($q) => $q->whereIn('funkcja_id', Funkcja::idsKierownictwaBudowy()))
            ->get()
            ->map(fn ($p) => $p->contact);

        $kp = Organization::whereIn('id', $orgIds)->whereNotNull('kierownik_projektu_id')->pluck('kierownik_projektu_id');
        $kontakty = $kontakty->concat(Contact::with('funkcja')->whereIn('id', $kp)->get());

        return $kontakty->filter()->unique('id')->values()->map(fn (Contact $c) => [
            'id' => $c->id,
            'nazwa' => trim($c->first_name.' '.$c->last_name),
            'stanowisko' => $c->funkcja?->name,
            'telefon' => $c->phone ?: null,
        ]);
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
