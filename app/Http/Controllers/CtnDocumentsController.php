<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\CtnDocument;
use App\Services\CtnDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CtnDocumentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CtnDocuments/Index', [
            'filters' => Request::all('search', 'trashed'),
            'contactId' => Request::route('contact_id'),
            'documents' => CtnDocument::query()
                ->where('contact_id', Request::route('contact_id'))
                ->paginate(10)
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CtnDocuments/Create', [
            'contactId' => Request::route('contact_id')
        ]);
    }

    public function store(CtnDocumentService $documentService, StoreDocumentRequest $request, int $contactId): RedirectResponse
    {
        $redirect = Redirect::route('documents.index', ['contact_id' => $contactId]);

        try {
            $documentService->store(
                Request::file('document'),
                $contactId,
                Request::get('name'),
                Request::get('typ')
            );
        } catch (\Exception $e) {
            Log::info('Error while storing document: ' . $e->getMessage());
            return $redirect->with('error', 'Nie udało się dodać dokumentu');
        }
        return $redirect->with('success', 'Dodano dokument');
    }

    public function view(int $contactId, int $documentId): BinaryFileResponse
    {
        $document = CtnDocument::query()->where('id', $documentId)->first();

        if (!$document) {
            throw new \Exception('with ID ' . $documentId);
        }

        return response()->download(storage_path("app/" . $document->path));
    }

    public function delete(int $id, int $documentId): RedirectResponse
    {
        $document = CtnDocument::query()->where('id', $documentId)->first();

        if ($document) {
            $document->delete();
        }

        try {
            Storage::delete(storage_path("app/" . $document->path));
        } catch (\Exception $exception) {
            throw new \Exception('Cannot remove file ' . $document->path);
        }

        // @TODO remove file and add logger
        return Redirect::route('documents.index', ['contact_id' => $id, 'document_id' => $documentId])
            ->with('success', 'Usunięto dokument');
    }
}
