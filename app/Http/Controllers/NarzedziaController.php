<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
use App\Models\Narzedzia;
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
            'filters' => Request::all('search', 'trashed'),
            'narzedzia' => Narzedzia::filter(request()->only('search', 'trashed'))
                ->paginate(20)
                ->withQueryString()
        ]);
    }

    public function edit(Narzedzia $narzedzia): Response
    {
        return Inertia::render('Narzedzia/Edit', [
            'narzedzia' => [
                'id' => $narzedzia->id,
                'name' => $narzedzia->name,
                'numer_seryjny' => $narzedzia->numer_seryjny,
                'waznosc_badan' => $narzedzia->waznosc_badan ? $narzedzia->waznosc_badan->format('Y-m-d') : null,
                'ilosc_all' => $narzedzia->ilosc_all,
                'ilosc_budowa' => $narzedzia->ilosc_budowa,
                'ilosc_magazyn' => $narzedzia->ilosc_magazyn,
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
            'name' => ['required', 'max:50'],
            'numer_seryjny' => ['nullable'],
            'waznosc_badan' => ['nullable', 'date'],
            'ilosc_all' => ['nullable', 'numeric'],
        ]);

        try {
            $narzedzia->update($data);

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
        return Inertia('Narzedzia/Create');
    }

    public function store(
        StoreNarzedziaRequest $request,
        DocumentService $documentService
    ): RedirectResponse
    {
        try {
            /** @var Narzedzia $tool */
            $tool = Narzedzia::create([
                'numer_seryjny' => $request->get('numer_seryjny'),
                'waznosc_badan' => $request->get('waznosc_badan'),
                'name' => $request->get('name'),
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
