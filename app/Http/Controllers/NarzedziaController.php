<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
use Carbon\Carbon;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use App\Models\Organization;
use App\Models\ToolFile;
use App\Models\ToolWorkDate;
use App\Services\DocumentService;
use App\Services\MagazynSprzetu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;


class NarzedziaController extends Controller
{
    public function index(MagazynSprzetu $magazyn): Response
    {
        // Cicha naprawa: znajdź rekordy gdzie suma się nie zgadza i je zaktualizuj
        Narzedzia::query()
            ->whereRaw('ilosc_magazyn != (ilosc_all - ilosc_budowa)')
            ->orWhereNull('ilosc_magazyn')
            ->limit(50)
            ->get()
            ->each(function ($tool) {
                $tool->save();
            });

        $dzis = Carbon::today()->toDateString();

        // Relacje z serwisu — inaczej opis każdej sztuki kosztowałby zapytania.
        $sztuki = Narzedzia::filter(Request::only('search', 'trashed', 'wyswietlaj'))
            ->with($magazyn->relacje())
            ->orderBy('numer_seryjny')
            ->get();

        return Inertia::render('Narzedzia/Index', [
            'filters' => Request::all('search', 'trashed', 'wyswietlaj'),
            'grupy' => $magazyn->grupy($sztuki, $dzis),
            'budowy' => Organization::whereNull('deleted_at')
                ->orderBy('nazwaBud')
                ->get(['id', 'nazwaBud', 'warsztat'])
                ->map(fn (Organization $o) => [
                    'id' => $o->id,
                    'nazwaBud' => $o->nazwaBud,
                    'warsztat' => (bool) $o->warsztat,
                ]),
        ]);
    }

    /**
     * Rzeczywisty limit wielkości pliku, jaki przyjmie serwer. Bierzemy
     * najmniejszą z wartości PHP — walidacja aplikacji obiecywała 10 MB,
     * a serwer odrzucał wszystko powyżej 2 MB, przez co zapis milczał.
     */
    public static function limitPlikuMb(): int
    {
        $doBajtow = function (string $wartosc): int {
            $wartosc = trim($wartosc);
            $jednostka = strtolower(substr($wartosc, -1));
            $liczba = (int) $wartosc;

            return match ($jednostka) {
                'g' => $liczba * 1024 * 1024 * 1024,
                'm' => $liczba * 1024 * 1024,
                'k' => $liczba * 1024,
                default => $liczba,
            };
        };

        $limity = array_filter([
            $doBajtow((string) ini_get('upload_max_filesize')),
            $doBajtow((string) ini_get('post_max_size')),
        ]);

        return max(1, (int) floor(min($limity) / 1024 / 1024));
    }

    /** Czy pobyt trwa, dopiero się zacznie, czy już się skończył. */
    private function stanPobytu(ToolWorkDate $pobyt, string $dzis): string
    {
        if ($pobyt->end !== null && (string) $pobyt->end < $dzis) {
            return 'zakonczony';
        }

        if ($pobyt->start !== null && (string) $pobyt->start > $dzis) {
            return 'zaplanowany';
        }

        return 'trwa';
    }

    /**
     * Wydanie sprzętu na budowę wprost z listy magazynu: zaznaczone sztuki,
     * jedna budowa, jeden termin. Zajętych nie ruszamy — najpierw trzeba je
     * zdjąć z poprzedniej budowy.
     */
    public function przypisz(MagazynSprzetu $magazyn): RedirectResponse
    {
        $dane = Request::validate([
            'narzedzia_ids' => ['required', 'array', 'min:1'],
            'narzedzia_ids.*' => ['integer', 'exists:narzedzias,id'],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ], [
            'narzedzia_ids.required' => 'Zaznacz co najmniej jedną sztukę.',
            'organization_id.required' => 'Wybierz budowę.',
            'start.required' => 'Podaj datę od.',
            'end.after_or_equal' => 'Data do nie może być wcześniejsza niż data od.',
        ]);

        $dzis = Carbon::today()->toDateString();
        $zajete = [];
        $wydane = 0;

        foreach (Narzedzia::whereIn('id', $dane['narzedzia_ids'])->with('toolWorkDates')->get() as $narzedzie) {
            if ($magazyn->trwajacePrzypisanie($narzedzie, $dzis)) {
                $zajete[] = trim($narzedzie->name.' '.($narzedzie->numer_seryjny ?: ''));
                continue;
            }

            ToolWorkDate::create([
                'narzedzia_id' => $narzedzie->id,
                'organization_id' => $dane['organization_id'],
                'narzedzia_nb' => 1,
                'start' => $dane['start'],
                'end' => $dane['end'] ?? null,
            ]);

            // Licznik magazynowy trzymamy zgodny ze starym widokiem sprzętu
            // w karcie budowy — obie drogi mają pokazywać to samo.
            $narzedzie->ilosc_budowa = ($narzedzie->ilosc_budowa ?? 0) + 1;
            $narzedzie->save();

            $wydane++;
        }

        if ($wydane === 0) {
            return Redirect::route('narzedzia')
                ->with('error', 'Nic nie wydano — zaznaczony sprzęt jest już na budowie.');
        }

        $komunikat = 'Wydano na budowę: '.$wydane.' '.($wydane === 1 ? 'sztukę' : 'szt.').'.';

        if ($zajete) {
            return Redirect::route('narzedzia')
                ->with('error', $komunikat.' Pominięto (już na budowie): '.implode(', ', $zajete));
        }

        return Redirect::route('narzedzia')->with('success', $komunikat);
    }

    /** Zdjęcie sprzętu z budowy — powrót do magazynu. */
    public function zdejmij(ToolWorkDate $toolWorkDate): RedirectResponse
    {
        $narzedzie = Narzedzia::find($toolWorkDate->narzedzia_id);

        if ($narzedzie) {
            $narzedzie->ilosc_budowa = max(0, ($narzedzie->ilosc_budowa ?? 0) - (int) $toolWorkDate->narzedzia_nb);
            $narzedzie->save();
        }

        $toolWorkDate->delete();

        return Redirect::route('narzedzia')->with('success', 'Sprzęt wrócił do magazynu.');
    }

    public function edit(Narzedzia $narzedzia, MagazynSprzetu $magazyn): Response
    {
        $dzis = Carbon::today()->toDateString();
        $narzedzia->load($magazyn->relacje());
        return Inertia::render('Narzedzia/Edit', [
            'typy' => NarzedziaTyp::orderBy('name')->get(['id', 'name', 'kategoria']),
            'kategorie' => NarzedziaTyp::kategorie(),
            'limitPlikuMb' => self::limitPlikuMb(),
            'narzedzia' => [
                'id' => $narzedzia->id,
                'name' => $narzedzia->name,
                'numer_seryjny' => $narzedzia->numer_seryjny,
                'waznosc_badan' => $narzedzia->waznosc_badan ? $narzedzia->waznosc_badan->format('Y-m-d') : null,
                'narzedzia_typ_id' => $narzedzia->narzedzia_typ_id,
                'ilosc_all' => $narzedzia->ilosc_all,
                'ilosc_budowa' => $narzedzia->ilosc_budowa,
                'ilosc_magazyn' => $narzedzia->ilosc_magazyn,
                // Gdzie sztuka jest teraz — null oznacza magazyn.
                'gdzie_jest' => $magazyn->sztuka($narzedzia, $dzis)['budowa'],
                'badania_status' => $magazyn->statusBadan($narzedzia, $dzis),
                // Cała historia pobytów, od najnowszego.
                'pobyty' => $narzedzia->toolWorkDates
                    ->sortByDesc(fn (ToolWorkDate $t) => (string) ($t->start ?: ''))
                    ->map(fn (ToolWorkDate $t) => [
                        'id' => $t->id,
                        'budowa_id' => optional($t->organization)->id,
                        'nazwaBud' => optional($t->organization)->nazwaBud ?? 'budowa usunięta',
                        'od' => $t->start ? (string) $t->start : null,
                        'do' => $t->end ? (string) $t->end : null,
                        'stan' => $this->stanPobytu($t, $dzis),
                    ])
                    ->values(),
            ],
            'photos' => ToolFile::query()
                ->where('tool_id', $narzedzia->id)
                ->where('type', 'photo')
                ->get()
                ->map(fn ($toolFile) => [
                    'id' => $toolFile->id,
                    'name' => $toolFile->filename,
                    'type' => $toolFile->type,
                    'display' => true,
                    'path' => URL::route(
                        'image',
                        [
                            'path' => DocumentService::toolFilePath($narzedzia->id, $toolFile->filename),
                        ]
                    )
                ]),
            'documents' => ToolFile::query()
                ->where('tool_id', $narzedzia->id)
                ->where('type', 'document')
                ->get()
                ->map(fn ($toolFile) => [
                    'id' => $toolFile->id,
                    'name' => $toolFile->filename,
                    'type' => $toolFile->type,
                    'display' => false,
                    'path' => URL::route('narzedzia.download.file',
                        [
                            'path' => DocumentService::toolFilePath($narzedzia->id, $toolFile->filename),
                            'narzedzia' => $narzedzia,
                            'name' => $toolFile->filename
                        ]
                    )
                ]),

        ]);
    }

    public function update(
        Narzedzia $narzedzia,
        DocumentService $documentService
    ): RedirectResponse
    {
        $data = Request::validate([
            'narzedzia_typ_id' => ['nullable', 'integer', 'exists:narzedzia_typs,id'],
            'new_typ_name' => ['nullable', 'string', 'max:100'],
            'new_typ_kategoria' => ['nullable', 'string', 'max:100'],
            'numer_seryjny' => ['nullable'],
            'waznosc_badan' => ['nullable', 'date'],
            'ilosc_all' => ['nullable', 'numeric'],
        ]);

        [$typId, $typName] = $this->resolveTyp(
            $data['narzedzia_typ_id'] ?? null,
            $data['new_typ_name'] ?? null,
            $data['new_typ_kategoria'] ?? null
        );

        try {
            $narzedzia->update([
                'narzedzia_typ_id' => $typId ?? $narzedzia->narzedzia_typ_id,
                'name' => $typName ?? $narzedzia->name,
                'numer_seryjny' => $data['numer_seryjny'] ?? null,
                'waznosc_badan' => $data['waznosc_badan'] ?? null,
                'ilosc_all' => $data['ilosc_all'] ?? $narzedzia->ilosc_all,
            ]);

            /** save new photos and documents, remove these removed on dropzone */
            foreach (Request::file('photos') ?? [] as $file) {
                if ($documentService->hasToolFile($narzedzia->id, $file->getClientOriginalName())) {
                    continue;
                }
                $documentService->storeToolFile($file, $narzedzia->id, 'photo');
            }

            foreach (Request::file('documents') ?? [] as $file) {
                if ($documentService->hasToolFile($narzedzia->id, $file->getClientOriginalName())) {
                    continue;
                }
                $documentService->storeToolFile($file, $narzedzia->id, 'document');
            }

        } catch (\Exception $exception) {
            Log::error('Error while updating tool: ' . $exception->getMessage());
            return Redirect::back()->with('error', 'Wystąpił błąd podczas zapisu: ' . $exception->getMessage());
        }

        return Redirect::route('narzedzia')->with('success', 'Element poprawiony.');
    }

    public function destroy(Narzedzia $narzedzia, DocumentService $documentService): RedirectResponse
    {
        // Sprawdź czy narzędzie jest na jakiejś budowie
        $onBuild = ToolWorkDate::where('narzedzia_id', $narzedzia->id)->exists();

        if ($onBuild) {
            return Redirect::back()->with('error', 'Nie można usunąć narzędzia, które jest przypisane do budowy.');
        }

        $documentService->deleteFiles($narzedzia->id);
        $narzedzia->delete();

        return Redirect::route('narzedzia')->with('success', 'Usunięto.');
    }

    public function restore(Narzedzia $narzedzia): RedirectResponse
    {
        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create(): Response
    {
        return Inertia('Narzedzia/Create', [
            'typy' => NarzedziaTyp::orderBy('name')->get(['id', 'name', 'kategoria']),
            'kategorie' => NarzedziaTyp::kategorie(),
            'limitPlikuMb' => self::limitPlikuMb(),
        ]);
    }

    /** Wspólne: ustal typ (istniejący lub nowy) i zwróć [id, nazwa]. */
    private function resolveTyp($typId, $newName, $kategoria = null): array
    {
        $newName = trim((string) $newName);
        if ($newName !== '') {
            $typ = NarzedziaTyp::firstOrCreate(['name' => $newName]);

            // Kategoria dopisywana przy zakładaniu typu z formularza sprzętu —
            // bez niej nowy model stanąłby w magazynie osobno, obok swoich.
            $kategoria = trim((string) $kategoria);
            if ($kategoria !== '' && ! $typ->kategoria) {
                $typ->update(['kategoria' => $kategoria]);
            }

            return [$typ->id, $typ->name];
        }
        $typ = $typId ? NarzedziaTyp::find($typId) : null;
        return [$typ?->id, $typ?->name];
    }

    public function store(
        StoreNarzedziaRequest $request,
        DocumentService $documentService
    ): RedirectResponse
    {
        [$typId, $typName] = $this->resolveTyp(
            $request->get('narzedzia_typ_id'),
            $request->get('new_typ_name'),
            $request->get('new_typ_kategoria')
        );

        /** @var Narzedzia $tool */
        $tool = Narzedzia::create([
            'narzedzia_typ_id' => $typId,
            'name' => $typName,
            'numer_seryjny' => $request->get('numer_seryjny'),
            'waznosc_badan' => $request->get('waznosc_badan') ?: null,
            'ilosc_all' => $request->get('ilosc_all'),
            'ilosc_budowa' => 0,
        ]);

        // Sam sprzęt zapisujemy poza try — inaczej błąd zapisu meldował się
        // jako "nie udało się dodać plików" i chował prawdziwą przyczynę.
        try {
            foreach (Request::file('photos') ?? [] as $file) {
                $documentService->storeToolFile($file, $tool->id, 'photo');
            }

            foreach (Request::file('documents') ?? [] as $file) {
                $documentService->storeToolFile($file, $tool->id, 'document');
            }

        } catch (\Exception $exception) {
            Log::info('Error while storing tool document: ' . $exception->getMessage());

            return Redirect::route('narzedzia')->with('error', 'Nie udało się dodać plików');
        }

        return Redirect::route('narzedzia')->with('success', 'Zapisano.');
    }

    public function deleteToolFile(
        Narzedzia $narzedzia,
        DocumentService $documentService
    ): JsonResponse
    {
        foreach (Request::get('files') as $name) {
            $documentService->deleteToolFile($narzedzia->id, $name);
        }

        return new JsonResponse();
    }

    public function download(Narzedzia $narzedzia, string $name): BinaryFileResponse
    {
        return response()->download(storage_path("app/" . DocumentService::toolFilePath($narzedzia->id, $name)));
    }
}
