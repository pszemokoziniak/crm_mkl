<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Factory\BuildTimeShiftFactory;
use App\Http\Requests\BuildTimeShiftRequest;
use App\Services\BuildsExcelExporter;
use App\Services\BuildTimeShiftCreator;
use App\Services\BuildTimeShiftsExcelExporter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Services\UrlopyBezWniosku;
use App\Models\ZgloszenieKierownika;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\BuildingTimeSheet as BuildingTimeSheetModel;
use App\Models\PobranieKcp;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class BuildingTimeSheet extends Controller
{
    /**
     * Ile dni wstecz kierownik budowy może jeszcze uzupełnić KCP.
     * Dotąd reguła istniała tylko w przeglądarce (i liczyła 3 dni od
     * początku wyświetlanego miesiąca, nie od konkretnego dnia), więc
     * samo wysłanie żądania omijało ją w całości.
     */
    public const DNI_WSTECZ_KIEROWNIK = 7;

    /**
     * Czy ten użytkownik może ruszyć ten dzień na tej budowie.
     * Biuro i kadry mogą zawsze — poprawki po zamknięciu miesiąca to ich
     * rola. Kierownika ogranicza okno 7 dni i zamknięcie miesiąca.
     *
     * @return string|null powód odmowy albo null, gdy wolno
     */
    private function powodOdmowy(int $build, string $dzien): ?string
    {
        $user = Auth::user();

        if (! $user || ! $user->prowadziBudowy()) {
            return null;
        }

        $data = Carbon::parse($dzien)->startOfDay();

        if (PobranieKcp::czyZamkniete($build, $data)) {
            return 'KCP za '.$data->format('m.Y').' zostało pobrane przez kadry i jest zamknięte. '
                .'Poprawki zgłoś do biura.';
        }

        if ($data->lt(Carbon::today()->subDays(self::DNI_WSTECZ_KIEROWNIK))) {
            // Bez nazwy roli: ten sam limit obowiązuje kierownika projektu,
            // a komunikat mówił mu "Kierownik budowy".
            return 'KCP uzupełnia się najwyżej '.self::DNI_WSTECZ_KIEROWNIK
                .' dni wstecz. Ten dzień jest starszy — zgłoś go do biura.';
        }

        return null;
    }

    public function view(int $build, Request $request): Response
    {
        $date = $request->query->get('date');
        $timeShifts = BuildTimeShiftFactory::create($build, $date);
        $date = BuildTimeShiftFactory::getBuildDate($date);

        return Inertia::render('Building/Index',
            [
                'date' => $date,
                'month' => $date->monthName,
                'timeSheets' => $timeShifts,
                'timeSheetsOrder' => array_keys((array)$timeShifts),
                'build' => $build,
                'shiftStatuses' => $this->getShiftStatuses()->all(),
                'user_owner' => Auth::user()->owner,
                'diffDays' => Carbon::today()->diffInDays($date),
                'dniWstecz' => self::DNI_WSTECZ_KIEROWNIK,
                // Miesiąc zamknięty przez kadry — kierownik już go nie rusza.
                'zamkniety' => $this->opisZamkniecia($build, $date),
                // Urlopy wpisane w tym miesiącu bez skanu wniosku — kierownik
                // dokłada skan przez zgłoszenie do kadr, nie przez blokadę zapisu.
                'urlopyBezWniosku' => app(UrlopyBezWniosku::class)->dla(
                    [$build],
                    $date->copy()->startOfMonth()->toDateString(),
                    $date->copy()->endOfMonth()->toDateString(),
                ),
                'rodzajeZgloszen' => ZgloszenieKierownika::RODZAJE,
                'buildDetails' => $this->getBuildHeaders($build)
            ]
        );
    }

    public function store(BuildTimeShiftRequest $request): JsonResponse
    {
        $odmowa = $this->powodOdmowy((int) $request->get('build'), $request->get('day'));

        if ($odmowa) {
            return new JsonResponse(['status' => 'error', 'message' => $odmowa], ResponseAlias::HTTP_FORBIDDEN);
        }

        try {
            BuildingTimeSheetModel::updateOrCreate(
                [
                    'organization_id' => $request->get('build'),
                    'contact_id' => $request->get('id'),
                    'work_day' => new \DateTimeImmutable($request->get('day'))
                ],
                [
                    'work_from' => (new \DateTimeImmutable($request->get('day')))->setTime(
                        (int)$request->get('from')['hours'],
                        (int)$request->get('from')['minutes']),
                    'work_to' => (new \DateTimeImmutable($request->get('day')))->setTime(
                        (int)$request->get('to')['hours'],
                        (int)$request->get('to')['minutes']
                    ),
                    'shift_status_id' => $request->get('status') ?? null,
                    'effective_work_time' => sprintf('%02d:%02d', (int)$request->get('workTime')['hours'], (int)$request->get('workTime')['minutes']),
                    'reduced_working_hours' => $request->get('reducedWorkingHours') ?? false
                ]
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'ok']);
    }

    public function delete(BuildTimeShiftRequest $request): RedirectResponse
    {
        $odmowa = $this->powodOdmowy((int) $request->get('build'), $request->get('day'));

        if ($odmowa) {
            return Redirect::back()->with('error', $odmowa);
        }

        $work_day = new DateTimeImmutable($request->get('day'));
        BuildingTimeSheetModel::where('organization_id', $request->get('build'))
            ->where('contact_id', $request->get('id'))
            ->where('work_day', $work_day->format('Y-m-d H:i:s'))->delete();

        return Redirect::back()->with('success', 'Godziny pracy usunięte.');
    }

    public function excelExport(int $build, Request $request): BinaryFileResponse
    {
        $date = $request->query->get('date');

        $timeShifts = BuildTimeShiftFactory::create($build, $date);
        $buildForDate = BuildTimeShiftFactory::getBuildDate($date);
        $shiftStatuses = $this->getShiftStatuses()->all();

        $buildName = $this->getBuildHeaders($build)->nazwaBud;

        // Pobranie przez kadry po zakończeniu miesiąca zamyka ten miesiąc.
        PobranieKcp::zapiszJesliZamyka($build, $buildForDate, Auth::user());

        $plik = (new BuildTimeShiftsExcelExporter($shiftStatuses))
            ->generate($timeShifts, $buildForDate, $buildName)
            ->export();

        // Nazwa pliku mówi, czego dotyczy: dotąd każdy eksport nazywał się
        // "kcp.xlsx" i po pobraniu kilku nie dało się ich rozróżnić.
        $nazwa = sprintf(
            'KCP %s %s.xlsx',
            preg_replace('/[^\p{L}\p{N} _-]+/u', '', (string) $buildName),
            $buildForDate->format('Y-m')
        );

        return response()->download($plik, $nazwa)->deleteFileAfterSend(true);
    }

    public function reportIndex(): Response
    {
        return Inertia::render('Reports/MonthReport');
    }

    public function buildsReport(Request $request): BinaryFileResponse
    {
        $date = BuildTimeShiftFactory::getBuildDate(
            $request->query->get('date')
        );

        $period = CarbonPeriod::create(
            $date->clone()->toImmutable()->firstOfMonth(),
            $date->clone()->toImmutable()->lastOfMonth()
        );

        $result = $this
            ->getWorkersOnBuildForPeriod($period)
            ->groupBy('contact_id');

        $plik = (new BuildsExcelExporter())
            ->generate(
                $result,
                $period,
                $this->bezWpisowWMiesiacu($period, $result->keys()->all()),
                $this->budowyBezKcp($period)
            )
            ->export();

        $nazwa = 'Podsumowanie miesiaca '.$period->first()->format('Y-m').'.xlsx';

        return response()->download($plik, $nazwa)->deleteFileAfterSend(true);
    }

    /**
     * Pracownicy pominięci w KCP: przypisani w tym miesiącu do budowy, która
     * KCP prowadzi, ale bez ani jednego własnego wpisu. To ich trzeba dopytać.
     *
     * Budowy, gdzie nikt nic nie wypełnił, zostają poza listą — inaczej raport
     * za wrzesień otwierał się 150 nazwiskami z budów, na których KCP w ogóle
     * nie ruszono, i gubił dwanaście wierszy, o które chodzi. Takie budowy
     * wymienia notka pod tabelą.
     *
     * @param  array<int, int|string>  $zWpisami
     */
    private function bezWpisowWMiesiacu(CarbonPeriod $period, array $zWpisami): Collection
    {
        $pierwszy = $period->first()->format('Y-m-d');
        $ostatni = $period->last()->format('Y-m-d');

        $budowyZKcp = DB::table('building_time_sheets')
            ->whereBetween('work_day', [$pierwszy, $ostatni])
            ->distinct()->pluck('organization_id');

        return DB::table('contact_work_dates', 'cwd')
            ->whereIn('cwd.organization_id', $budowyZKcp)
            ->join('contacts', 'contacts.id', '=', 'cwd.contact_id')
            ->join('organizations', 'organizations.id', '=', 'cwd.organization_id')
            ->whereNull('cwd.deleted_at')
            ->whereNull('contacts.deleted_at')
            ->whereDate('cwd.start', '<=', $ostatni)
            ->where(function ($query) use ($pierwszy) {
                $query->whereNull('cwd.end')->orWhereDate('cwd.end', '>=', $pierwszy);
            })
            ->whereNotIn('contacts.id', $zWpisami ?: [0])
            ->select('contacts.id', 'contacts.first_name', 'contacts.last_name', 'organizations.nazwaBud')
            ->orderBy('contacts.last_name')->orderBy('contacts.first_name')
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Budowy z obsadą w tym miesiącu, na których nikt nie wypełnił ani jednego
     * dnia. Jedna notka zamiast kilkudziesięciu wierszy "brak wpisów".
     *
     * @return array<int, string>
     */
    private function budowyBezKcp(CarbonPeriod $period): array
    {
        $pierwszy = $period->first()->format('Y-m-d');
        $ostatni = $period->last()->format('Y-m-d');

        $zKcp = DB::table('building_time_sheets')
            ->whereBetween('work_day', [$pierwszy, $ostatni])
            ->distinct()->pluck('organization_id');

        return DB::table('contact_work_dates', 'cwd')
            ->join('organizations', 'organizations.id', '=', 'cwd.organization_id')
            ->whereNull('cwd.deleted_at')
            ->whereNull('organizations.deleted_at')
            ->whereDate('cwd.start', '<=', $ostatni)
            ->where(function ($query) use ($pierwszy) {
                $query->whereNull('cwd.end')->orWhereDate('cwd.end', '>=', $pierwszy);
            })
            ->whereNotIn('cwd.organization_id', $zKcp)
            ->distinct()
            ->orderBy('organizations.nazwaBud')
            ->pluck('organizations.nazwaBud')
            ->all();
    }

    /**
     * @return array{okres: string, kiedy: string, kto: ?string}|null
     */
    private function opisZamkniecia(int $build, Carbon $miesiac): ?array
    {
        $pobranie = PobranieKcp::with('user')
            ->where('organization_id', $build)
            ->where('okres', PobranieKcp::okres($miesiac))
            ->first();

        if (! $pobranie) {
            return null;
        }

        return [
            'okres' => $miesiac->format('m.Y'),
            'kiedy' => optional($pobranie->created_at)->format('d.m.Y'),
            'kto' => $pobranie->user ? trim($pobranie->user->last_name.' '.$pobranie->user->first_name) : null,
        ];
    }

    private function getShiftStatuses(): Collection
    {
        return DB::table('shift_status', 's')->where('deleted_at', null)->get();
    }

    private function getBuildHeaders(int $buildId): mixed
    {
        return DB::table('organizations', 'o')
            // id potrzebne w nagłówku, żeby dało się wrócić do karty budowy
            ->select('o.id', 'o.nazwaBud', 'o.numerBud')
            ->where('o.id', $buildId)
            ->first();
    }

    /**
     * @param CarbonPeriod $period
     * @return Collection
     */
    public function getWorkersOnBuildForPeriod(CarbonPeriod $period): Collection
    {
        return DB::table('building_time_sheets', 'b')
            ->join('organizations', 'organizations.id', '=', 'b.organization_id')
            ->join('contacts', 'contacts.id', '=', 'b.contact_id')
            ->leftJoin('shift_status', 'shift_status.id', '=', 'b.shift_status_id')
            ->whereBetween('work_day', [$period->first()->format('Y-m-d'), $period->last()->format('Y-m-d')])
            ->select(
                'contact_id', 'work_day', 'numerBud', 'organizations.nazwaBud',
                'code', 'shift_status.title as status_nazwa',
                'first_name', 'last_name', 'effective_work_time'
            )
            ->orderBy('contacts.last_name')
            ->orderBy('contacts.first_name')
            ->orderBy('b.work_day')
            ->get();
    }
}
