<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Wpis zastąpiony nowszym tego samego rodzaju.
 *
 * Dwa badania okresowe, jedno ważne do 2028 i jedno wygasłe w 2024, to nie
 * są dwa problemy — to jedno aktualne badanie i jego historia. Tej historii
 * nie kasujemy (przy kontroli liczy się, czy ktoś BYŁ przebadany w danym
 * miesiącu, a razem z wpisem zniknąłby skan), ale na liście ma być widać,
 * który wpis obowiązuje.
 *
 * Tej samej reguły używa pulpit, żeby stare wpisy nie wisiały jako
 * przeterminowane obok nowych.
 *
 * Wpis bez daty końca nikogo nie zastępuje i sam nie bywa zastąpiony —
 * nie ma czego porównać.
 */
class ZastapioneWpisy
{
    /**
     * Identyfikatory wpisów, które mają nowszy odpowiednik.
     *
     * @param  string  $tabela  np. "badanias"
     * @param  string|null  $kolumnaTypu  np. "badaniaTyp_id"; null, gdy rodzaju nie ma (A1, PBiOZ)
     * @return array<int, int>
     */
    public function idsDla(string $tabela, int $contactId, ?string $kolumnaTypu = null): array
    {
        return DB::table($tabela.' as w')
            ->where('w.contact_id', $contactId)
            ->whereNull('w.deleted_at')
            ->whereNotNull('w.end')
            ->whereExists(function ($zapytanie) use ($tabela, $kolumnaTypu) {
                $zapytanie->select(DB::raw(1))
                    ->from($tabela.' as nowszy')
                    ->whereColumn('nowszy.contact_id', 'w.contact_id')
                    ->whereColumn('nowszy.end', '>', 'w.end')
                    ->whereNull('nowszy.deleted_at');

                if ($kolumnaTypu) {
                    $zapytanie->whereColumn('nowszy.'.$kolumnaTypu, 'w.'.$kolumnaTypu);
                }
            })
            ->pluck('w.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
