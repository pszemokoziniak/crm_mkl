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
    public function index(): Response
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

        $sztuki = Narzedzia::filter(Request::only('search', 'trashed', 'wyswietlaj'))
            ->with([
                'typ',
                // Zdjęcia i przypisania jednym zapytaniem — miniaturka ani budowa
                // nie mogą kosztować zapytania na każdy wiersz.
                'files' => fn ($query) => $query->where('type', 'photo')->orderBy('id'),
                'toolWorkDates.organization',
            ])
            ->orderBy('numer_seryjny')
            ->get();

        return Inertia::render('Narzedzia/Index', [
            'filters' => Request::all('search', 'trashed', 'wyswietlaj'),
            'grupy' => $this->pogrupuj($sztuki, $dzis),
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
     * Dwa poziomy: kategoria (np. "Kontener") zbiera modele (Kontener 3m,
     * Kontener 6m), a te — pojedyncze sztuki. Sprzęt bez kategorii pokazuje
     * się wprost jako swój model, bez zbędnego poziomu.
     *
     * @param  \Illuminate\Support\Collection<int, Narzedzia>  $sztuki
     * @return array<int, array<string, mixed>>
     */
    private function pogrupuj($sztuki, string $dzis): array
    {
        return $sztuki
            ->groupBy(fn (Narzedzia $n) => optional($n->typ)->kategoria ?: 'model:'.$this->nazwaModelu($n))
            ->map(function ($wKategorii, $klucz) use ($dzis) {
                $modele = $wKategorii
                    ->groupBy(fn (Narzedzia $n) => $this->nazwaModelu($n))
                    ->map(fn ($grupa, $nazwa) => $this->opiszModel($grupa, $nazwa, $dzis))
                    ->sortBy('nazwa', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values();

                $bezKategorii = str_starts_with((string) $klucz, 'model:');

                return [
                    'klucz' => (string) $klucz,
                    'nazwa' => $bezKategorii ? $modele->first()['nazwa'] : (string) $klucz,
                    // Jeden model bez kategorii nie potrzebuje poziomu pośredniego.
                    'ma_modele' => ! $bezKategorii,
                    'photo' => $modele->firstWhere('photo', '!=', null)['photo'] ?? null,
                    'sztuk' => $modele->sum('sztuk'),
                    'dostepne' => $modele->sum('dostepne'),
                    'na_budowie' => $modele->sum('na_budowie'),
                    'badania_uwaga' => $modele->sum('badania_uwaga'),
                    'modele' => $modele,
                ];
            })
            ->sortBy('nazwa', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** Model to wpis ze słownika typów; bez niego zostaje nazwa sprzętu. */
    private function nazwaModelu(Narzedzia $narzedzia): string
    {
        return optional($narzedzia->typ)->name ?: (string) $narzedzia->name;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Narzedzia>  $grupa
     * @return array<string, mixed>
     */
    private function opiszModel($grupa, string $nazwa, string $dzis): array
    {
        $opisane = $grupa->map(fn (Narzedzia $n) => $this->opiszSztuke($n, $dzis))->values();

        return [
            'klucz' => 'model:'.$nazwa,
            'nazwa' => $nazwa,
            'photo' => $this->thumbnailUrl($grupa->first(fn (Narzedzia $n) => $n->files->isNotEmpty()) ?? $grupa->first()),
            'sztuk' => $opisane->count(),
            'na_budowie' => $opisane->where('budowa', '!=', null)->count(),
            'dostepne' => $opisane->whereNull('budowa')->count(),
            // Ile sztuk ma nieważne albo kończące się badania — żeby było
            // widać na zwiniętym wierszu, bez rozwijania.
            'badania_uwaga' => $opisane->whereIn('badania_status', ['po_terminie', 'wkrotce'])->count(),
            'sztuki' => $opisane,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function opiszSztuke(Narzedzia $narzedzia, string $dzis): array
    {
        // Sprzęt jest na budowie, dopóki przypisanie się nie skończyło.
        // Stare wpisy nie mają dat — traktujemy je jako trwające.
        $pobyt = $narzedzia->toolWorkDates
            ->filter(fn (ToolWorkDate $t) => (int) $t->narzedzia_nb > 0 && $t->organization)
            ->first(fn (ToolWorkDate $t) => $t->end === null || (string) $t->end >= $dzis);

        return [
            'id' => $narzedzia->id,
            'numer_seryjny' => $narzedzia->numer_seryjny ?: null,
            'waznosc_badan' => $this->dataBadan($narzedzia),
            'badania_status' => $this->statusBadan($narzedzia, $dzis),
            'photo' => $this->thumbnailUrl($narzedzia),
            'budowa' => $pobyt ? [
                'przypisanie_id' => $pobyt->id,
                'id' => $pobyt->organization->id,
                'nazwaBud' => $pobyt->organization->nazwaBud,
                'do' => $pobyt->end ? (string) $pobyt->end : null,
            ] : null,
        ];
    }

    /**
     * Część dat badań to śmieci z importu (rok 9999 albo -0001) — takie
     * traktujemy jak brak daty, zamiast straszyć nimi na liście.
     */
    private function dataBadan(Narzedzia $narzedzia): ?string
    {
        $data = $narzedzia->waznosc_badan;

        if (! $data || $data->year < 2000 || $data->year > 2100) {
            return null;
        }

        return $data->format('Y-m-d');
    }

    private function statusBadan(Narzedzia $narzedzia, string $dzis): ?string
    {
        $data = $this->dataBadan($narzedzia);

        if (! $data) {
            return 'brak';
        }

        if ($data < $dzis) {
            return 'po_terminie';
        }

        return $data <= Carbon::parse($dzis)->addDays(30)->toDateString() ? 'wkrotce' : 'wazne';
    }

    /**
     * Wydanie sprzętu na budowę wprost z listy magazynu: zaznaczone sztuki,
     * jedna budowa, jeden termin. Zajętych nie ruszamy — najpierw trzeba je
     * zdjąć z poprzedniej budowy.
     */
    public function przypisz(): RedirectResponse
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
            $naBudowie = $narzedzie->toolWorkDates
                ->filter(fn (ToolWorkDate $t) => (int) $t->narzedzia_nb > 0)
                ->first(fn (ToolWorkDate $t) => $t->end === null || (string) $t->end >= $dzis);

            if ($naBudowie) {
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

    /** Miniaturka pierwszego zdjęcia sprzętu — skalowana przez Glide. */
    private function thumbnailUrl(Narzedzia $narzedzia): ?string
    {
        $photo = $narzedzia->files->first();

        if (! $photo) {
            return null;
        }

        return URL::route('image', [
            'path' => DocumentService::toolFilePath($narzedzia->id, $photo->filename),
            'w' => 96,
            'h' => 96,
            'fit' => 'crop',
        ]);
    }

    public function edit(Narzedzia $narzedzia): Response
    {
        return Inertia::render('Narzedzia/Edit', [
            'typy' => NarzedziaTyp::orderBy('name')->get(['id', 'name']),
            'narzedzia' => [
                'id' => $narzedzia->id,
                'name' => $narzedzia->name,
                'numer_seryjny' => $narzedzia->numer_seryjny,
                'waznosc_badan' => $narzedzia->waznosc_badan ? $narzedzia->waznosc_badan->format('Y-m-d') : null,
                'narzedzia_typ_id' => $narzedzia->narzedzia_typ_id,
                'ilosc_all' => $narzedzia->ilosc_all,
                'ilosc_budowa' => $narzedzia->ilosc_budowa,
                'ilosc_magazyn' => $narzedzia->ilosc_magazyn,
                'budowy' => $narzedzia->toolWorkDates()
                    ->with('organization')
                    ->get()
                    ->filter(fn ($t) => (int) $t->narzedzia_nb > 0 && $t->organization)
                    ->map(fn ($t) => [
                        'id' => $t->organization->id,
                        'nazwaBud' => $t->organization->nazwaBud,
                        'qty' => (int) $t->narzedzia_nb,
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
            'numer_seryjny' => ['nullable'],
            'waznosc_badan' => ['nullable', 'date'],
            'ilosc_all' => ['nullable', 'numeric'],
        ]);

        [$typId, $typName] = $this->resolveTyp($data['narzedzia_typ_id'] ?? null, $data['new_typ_name'] ?? null);

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
            'typy' => NarzedziaTyp::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Wspólne: ustal typ (istniejący lub nowy) i zwróć [id, nazwa]. */
    private function resolveTyp($typId, $newName): array
    {
        $newName = trim((string) $newName);
        if ($newName !== '') {
            $typ = NarzedziaTyp::firstOrCreate(['name' => $newName]);
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
        try {
            [$typId, $typName] = $this->resolveTyp($request->get('narzedzia_typ_id'), $request->get('new_typ_name'));

            /** @var Narzedzia $tool */
            $tool = Narzedzia::create([
                'narzedzia_typ_id' => $typId,
                'name' => $typName,
                'numer_seryjny' => $request->get('numer_seryjny'),
                'waznosc_badan' => $request->get('waznosc_badan'),
                'ilosc_all' => $request->get('ilosc_all'),
                'ilosc_budowa' => 0,
            ]);

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
