<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\CtnDocument;
use App\Models\DokumentyTyp;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
            'contactId' => (int) Request::route('contact_id'),
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', Request::route('contact_id'))
                ->paginate(10)
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CtnDocuments/Create', [
            'contactId' => Request::route('contact_id'),
            'dokumentyTyps' => DokumentyTyp::all()
        ]);
    }

    public function store(DocumentService $documentService, StoreDocumentRequest $request, int $contactId): RedirectResponse
    {
        $redirect = Redirect::route('documents.index', ['contact_id' => $contactId]);

        try {
            foreach (Request::file('documents') as $file) {
                $documentService->storeCtnDocument(
                    $file,
                    $contactId,
                    Request::get('name'),
                    Request::get('typ'),
                );
            }
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

        return Redirect::route(
            'documents.index',
            [
                'contact_id' => $id,
                'document_id' => $documentId
            ])
            ->with('success', 'Usunięto dokument');
    }

    public function deleteLek(int $id, int $documentId): RedirectResponse
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
        return Redirect::route('badania.index', ['contact' => $id])
            ->with('success', 'Usunięto dokument');
    }

    public function deleteBhp(int $id, int $documentId): RedirectResponse
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
        return Redirect::route('bhp.index', ['contact' => $id])
            ->with('success', 'Usunięto dokument');
    }
    public function deleteUpr(int $id, int $documentId): RedirectResponse
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
        return Redirect::route('uprawnienia.index', ['contact' => $id])
            ->with('success', 'Usunięto dokument');
    }
    public function deleteA1(int $id, int $documentId): RedirectResponse
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
        return Redirect::route('a1.index', ['contact' => $id])
            ->with('success', 'Usunięto dokument');
    }
}
