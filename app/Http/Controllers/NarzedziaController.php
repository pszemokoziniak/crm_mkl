<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
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

        return Inertia::render('Narzedzia/Index', [
            'filters' => Request::all('search', 'trashed', 'wyswietlaj'),
            'narzedzia' => Narzedzia::filter(request()->only('search', 'trashed', 'wyswietlaj'))
                // Zdjęcia dociągamy jednym zapytaniem — miniaturka na liście
                // nie może kosztować zapytania na każdy wiersz.
                ->with(['files' => fn ($query) => $query->where('type', 'photo')->orderBy('id')])
                // Na których budowach jest sprzęt — jednym zapytaniem, bez N+1.
                ->with(['toolWorkDates.organization'])
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Narzedzia $narzedzia) => [
                    'id' => $narzedzia->id,
                    'name' => $narzedzia->name,
                    'numer_seryjny' => $narzedzia->numer_seryjny,
                    'ilosc_all' => $narzedzia->ilosc_all,
                    'ilosc_budowa' => $narzedzia->ilosc_budowa,
                    'ilosc_magazyn' => $narzedzia->ilosc_magazyn,
                    'deleted_at' => $narzedzia->deleted_at ?? null,
                    'photo' => $this->thumbnailUrl($narzedzia),
                    'budowy' => $narzedzia->toolWorkDates
                        ->filter(fn ($t) => (int) $t->narzedzia_nb > 0 && $t->organization)
                        ->map(fn ($t) => [
                            'id' => $t->organization->id,
                            'nazwaBud' => $t->organization->nazwaBud,
                            'qty' => (int) $t->narzedzia_nb,
                        ])
                        ->values(),
                ]),
        ]);
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
