<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Co pracownik już ma wpisane w danej zakładce — do podpowiedzi w formularzu
 * dodawania.
 *
 * Nie pytamy "czy skasować poprzedni", bo odpowiedź zawsze brzmiałaby tak
 * samo, a skasowanie zabrałoby skan, czyli dowód. Mówimy tylko, co się
 * stanie: nowy wpis stanie się aktualny, poprzedni zostanie w historii.
 */
class IstniejaceWpisy
{
    /**
     * Najświeższy wpis każdego rodzaju.
     *
     * @return array<int, array{do: ?string, bezterminowy: bool, ile: int}>
     *         klucz: id rodzaju albo 0, gdy rodzaju nie ma (A1, PBiOZ)
     */
    public function dla(string $tabela, int $contactId, ?string $kolumnaTypu = null): array
    {
        $wiersze = DB::table($tabela)
            ->where('contact_id', $contactId)
            ->whereNull('deleted_at')
            ->get(array_filter(['end', $kolumnaTypu]));

        $poRodzajach = [];

        foreach ($wiersze as $wiersz) {
            $klucz = $kolumnaTypu ? (int) $wiersz->{$kolumnaTypu} : 0;
            $poRodzajach[$klucz][] = $wiersz->end;
        }

        $wynik = [];

        foreach ($poRodzajach as $klucz => $konce) {
            $bezterminowy = in_array(null, $konce, true);
            $zDatami = array_filter($konce);

            $wynik[$klucz] = [
                // Wpis bez daty końca bije każdy z datą — obowiązuje bezterminowo.
                'do' => $bezterminowy || $zDatami === [] ? null : max($zDatami),
                'bezterminowy' => $bezterminowy,
                'ile' => count($konce),
            ];
        }

        return $wynik;
    }
}
