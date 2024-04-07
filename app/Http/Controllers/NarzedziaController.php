<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
use App\Models\CtnDocument;
use App\Models\Narzedzia;
use App\Models\ToolFile;
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
        return Inertia::render('Narzedzia/Index', [
            'narzedzia' => Narzedzia::all()
        ]);
    }

    public function edit(Narzedzia $narzedzia): Response
    {
        return Inertia::render('Narzedzia/Edit', [
            'narzedzia' => [
                'id' => $narzedzia->id,
                'name' => $narzedzia->name,
                'numer_seryjny' => $narzedzia->numer_seryjny,
                'waznosc_badan' => $narzedzia->waznosc_badan,
                'ilosc_all' => $narzedzia->ilosc_all,
                'deleted_at' => $narzedzia->deleted_at,
                'files' => $narzedzia->files
            ],
            'photos' => ToolFile::query()
                ->where('tool_id', $narzedzia->id)
                ->where('type', 'photo')
                ->get()
                ->map(fn ($toolFile) => [
                    'id' => $toolFile->id,
                    'name' => $toolFile->filename,
                    'type' => $toolFile->type,
                    'display' => false,
                    'path' => URL::route(
                        'image',
                        [
                            'path' => DocumentService::toolFilePath($narzedzia->id, $toolFile->filename),
                            'w' => 260, 'h' => 260, 'fit' => 'crop'
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
        Request $request,
        Narzedzia $narzedzia,
        DocumentService $documentService
    ): RedirectResponse
    {
        try {
            $narzedzia->update(
                Request::validate([
                    'name' => ['required', 'max:50'],
                    'numer_seryjny' => ['required'],
                    'waznosc_badan' => ['required', 'date'],
                    'ilosc_all' => ['required'],
                ])
            );

            /** save new photos and documents, remove these removed on dropzone */
            foreach (Request::file('photos') as $file) {

                // resent with form - exists
                if ($documentService->hasToolFile($narzedzia->id, $file->getClientOriginalName())) {
                    continue;
                }
                // not exists - add
                $documentService->storeToolFile($file, $narzedzia->id, 'photo');
            }

            foreach (Request::file('documents') as $file) {

                if ($documentService->hasToolFile($narzedzia->id, $file->getClientOriginalName())) {
                    continue;
                }

                $documentService->storeToolFile($file, $narzedzia->id, 'document');
            }

        } catch (\Exception $exception) {
            // log
        }

        return Redirect::route('narzedzia')->with('success', 'Element poprawiony.');
    }

    public function destroy(Narzedzia $narzedzia, DocumentService $documentService): RedirectResponse
    {
        $documentService->deleteFiles($narzedzia->id);

        $narzedzia->delete();

        return Redirect::route('narzedzia')->with('success', 'Usunięto.');
    }

    public function restore(Narzedzia $narzedzia): RedirectResponse
    {
        $narzedzia->restore();

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
                'ilosc_magazyn' => $request->get('ilosc_all'),
            ]);

            foreach (Request::file('photos') as $file) {
                $documentService->storeToolFile($file, $tool->id, 'photo');
            }

            foreach (Request::file('documents') as $file) {
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
        DocumentService $documentService,
        Request $request
    ): JsonResponse
    {
        foreach ($request::all()['files'] as $name) {
            $documentService->deleteToolFile($narzedzia->id, $name);
        }

        return new JsonResponse();
    }

    public function download(Narzedzia $narzedzia, string $name): BinaryFileResponse
    {
        return response()->download(storage_path("app/" . DocumentService::toolFilePath($narzedzia->id, $name)));
    }
}
