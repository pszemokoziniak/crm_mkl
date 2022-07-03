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
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

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
        // @TODO to validate type of file, display errors on FE
        Request::validate([
            'name' => ['required', 'max:50'],
            'document' => ['nullable', 'image', 'mimes:pdf'],
        ]);

        $contactId = Request::route('contact_id');
        $document = Request::file('document');
        $path = 'documents/' . $contactId;

        $document->storeAs($path, $document->getClientOriginalName());

        CtnDocument::create(
            Request::get('name'),
            $path . '/' . $document->getClientOriginalName()
            , $contactId,
            $document->getClientOriginalName()
        )->save();

        return Redirect::route('contacts')->with('success', 'Zapisano dokument');
    }

    public function view(int $id, int $documentId): BinaryFileResponse
    {
        $document = CtnDocument::query()->where('id', $documentId)->first();

        if (!$document) {
            throw new FileNotFoundException('with ID ' . $documentId);
        }

        return response()->download(storage_path("app/" . $document->path));
    }
}
