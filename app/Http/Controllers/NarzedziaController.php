<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
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
            'files' => ToolFile::query()
                ->where('tool_id', $narzedzia->id)
                ->get()
                ->map(fn ($toolFile) => [
                    'id' => $toolFile->id,
                    'name' => $toolFile->filename,
                    'type' => $toolFile->type
                ])
        ]);
    }

    public function update(Request $req, Narzedzia $narzedzia): RedirectResponse
    {
        $narzedzia->update(
            Request::validate([
                'name' => ['required', 'max:50'],
                'numer_seryjny' => ['required'],
                'waznosc_badan' => ['required', 'date'],
                'ilosc_all' => ['required'],
                'photo_path' => ['required'],
            ])
        );

        return Redirect::route('narzedzia')->with('success', 'Element poprawiony.');
    }

    public function destroy(Narzedzia $narzedzia): RedirectResponse
    {
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
                'numer_seryjny' => Request::get('numer_seryjny'),
                'waznosc_badan' => Request::get('waznosc_badan'),
                'name' => Request::get('name'),
                'ilosc_all' => Request::get('ilosc_all'),
                'ilosc_budowa' => 0,
                'ilosc_magazyn' => Request::get('ilosc_all'),
            ]);

            foreach (Request::file('photos') as $file) {
                $documentService->storeToolDocument($file, $tool->id, 'photo');
            }

            foreach (Request::file('documents') as $file) {
                $documentService->storeToolDocument($file, $tool->id, 'document');
            }

        } catch (\Exception $exception) {
            Log::info('Error while storing tool document: ' . $exception->getMessage());

            return Redirect::route('narzedzia')->with('error', 'Nie udało się dodać plików');
        }

        return Redirect::route('narzedzia')->with('success', 'Zapisano.');
    }
}
