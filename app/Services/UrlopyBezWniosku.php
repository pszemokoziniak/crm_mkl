<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Holiday;
use App\Models\ZgloszenieKierownika;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Urlopy wpisane w KCP, do których nie ma wniosku. Kierownik wpisuje UW
 * dzień po dniu w KCP; firma chce mieć skan wniosku urlopowego. Nie
 * blokujemy zapisu — pokazujemy braki: w KCP, na pulpicie kierownika,
 * u kadr, i przypominamy mailem.
 *
 * Dzień jest "bez wniosku", gdy w KCP stoi kod urlopu wpisany ręcznie,
 * a nie zakrywa go ani nieobecność z karty pracownika (tę wstawiają kadry
 * na podstawie dokumentu), ani zgłoszenie urlopu ze skanem.
 */
class UrlopyBezWniosku
{
    /** Kody z KCP, za którymi ma stać wniosek. Zwolnienie (ZL) ma L4, nie wniosek. */
    public const KODY = ['UW', 'UO', 'UB', 'UŻ'];

    /** Przerwa między dniami, którą jeszcze sklejamy w jeden urlop (weekend). */
    private const SKLEJ_DO_DNI = 3;

    /**
     * @param int[]|null $orgIds null = wszystkie budowy
     * @return Collection<int, array<string, mixed>> po jednym wierszu na ciągły urlop
     */
    public function dla(?array $orgIds, string $od, string $do): Collection
    {
        $dni = DB::table('building_time_sheets as k')
            ->join('shift_status as s', 's.id', '=', 'k.shift_status_id')
            ->join('contacts as c', 'c.id', '=', 'k.contact_id')
            ->join('organizations as o', 'o.id', '=', 'k.organization_id')
            ->whereIn('s.code', self::KODY)
            ->whereNull('c.deleted_at')
            ->whereBetween('k.work_day', [$od, $do])
            ->when($orgIds !== null, fn ($q) => $q->whereIn('k.organization_id', $orgIds))
            ->orderBy('k.contact_id')->orderBy('k.work_day')
            ->get([
                'k.contact_id', 'k.organization_id', 'k.work_day', 's.code',
                'c.first_name', 'c.last_name', 'o.nazwaBud',
            ]);

        if ($dni->isEmpty()) {
            return collect();
        }

        $contactIds = $dni->pluck('contact_id')->unique()->values()->all();

        // Nieobecność z karty pracownika zakrywa dzień — dokument jest u kadr.
        $nieobecnosci = Holiday::whereIn('contact_id', $contactIds)
            ->where('start', '<=', $do)
            ->where(fn ($q) => $q->whereNull('end')->orWhere('end', '>=', $od))
            ->get(['contact_id', 'start', 'end'])
            ->groupBy('contact_id');

        // Zgłoszenie urlopu ze skanem — albo z wniosku złożonego z telefonu
        // i zatwierdzonego przez kierownika — zakrywa dni od–do.
        $zgloszenia = ZgloszenieKierownika::whereIn('contact_id', $contactIds)
            ->where('rodzaj', ZgloszenieKierownika::RODZAJ_URLOP)
            ->where(fn ($q) => $q->whereNotNull('plik_sciezka')->orWhereNotNull('wniosek_id'))
            ->where('status', '!=', ZgloszenieKierownika::STATUS_ODRZUCONE)
            ->get(['contact_id', 'od', 'do'])
            ->groupBy('contact_id');

        $bezWniosku = $dni->filter(function ($dzien) use ($nieobecnosci, $zgloszenia) {
            $data = Carbon::parse((string) $dzien->work_day)->toDateString();
            // Daty z Eloquenta przychodzą jako Carbon, z DB jako tekst — porównujemy po dniu.
            $dzienZ = fn ($w) => $w === null ? null : Carbon::parse((string) $w)->toDateString();
            $zakryty = fn ($lista, $odKol, $doKol) => collect($lista)->contains(
                fn ($w) => $dzienZ($w->$odKol) <= $data && ($w->$doKol === null || $dzienZ($w->$doKol) >= $data)
            );

            return ! $zakryty($nieobecnosci->get($dzien->contact_id, []), 'start', 'end')
                && ! $zakryty($zgloszenia->get($dzien->contact_id, []), 'od', 'do');
        });

        return $this->sklejWCiagi($bezWniosku);
    }

    /**
     * Dni tej samej osoby na tej samej budowie sklejamy w jeden urlop,
     * jeśli dzieli je najwyżej weekend.
     */
    private function sklejWCiagi(Collection $dni): Collection
    {
        $wynik = collect();

        foreach ($dni->groupBy(fn ($d) => $d->organization_id.':'.$d->contact_id) as $grupa) {
            $biezacy = null;
            foreach ($grupa->sortBy('work_day') as $d) {
                $data = Carbon::parse((string) $d->work_day);
                if ($biezacy && (int) $data->diffInDays(Carbon::parse($biezacy['do']), true) <= self::SKLEJ_DO_DNI) {
                    $biezacy['do'] = $data->toDateString();
                    $biezacy['dni']++;
                    continue;
                }
                if ($biezacy) {
                    $wynik->push($biezacy);
                }
                $biezacy = [
                    'organization_id' => (int) $d->organization_id,
                    'budowa' => $d->nazwaBud,
                    'contact_id' => (int) $d->contact_id,
                    'pracownik' => trim($d->last_name.' '.$d->first_name),
                    'kod' => $d->code,
                    'od' => $data->toDateString(),
                    'do' => $data->toDateString(),
                    'dni' => 1,
                ];
            }
            if ($biezacy) {
                $wynik->push($biezacy);
            }
        }

        return $wynik->sortBy([['od', 'asc'], ['pracownik', 'asc']])->values();
    }
}
