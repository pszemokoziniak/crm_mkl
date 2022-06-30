<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CtnDocument;
use Illuminate\Http\RedirectResponse;
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

    public function store(): RedirectResponse
    {
        // @TODO to validate type of file
        Request::validate([
            'name' => ['required', 'max:50'],
            'document' => ['nullable', 'image'],
        ]);

        // @TODO to clean
        $contactId = Request::route('contact_id');
        $document = Request::file('document');
        $path = 'documents/' . $contactId;
        $document->storeAs($path, $document->getClientOriginalName());

        $documentEntity = new CtnDocument();
        $documentEntity->name = Request::get('name');
        $documentEntity->path = $path . '/' . $document->getClientOriginalName();
        $documentEntity->contact_id = $contactId;
        $documentEntity->filename = $document->getClientOriginalName();
        $documentEntity->save();

        return Redirect::route('contacts')->with('success', 'Zapisano dokument');
    }

    public function view(): BinaryFileResponse
    {
        $document = CtnDocument::query()->where('id', Request::route('document_id'))->first();

        return response()->file(storage_path("app/" . $document->path), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
