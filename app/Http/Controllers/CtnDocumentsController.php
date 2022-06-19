<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\CtnDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use function PHPUnit\Framework\assertObjectNotHasAttribute;

class CtnDocumentsController extends Controller
{

    public function index(): Response
    {
        return Inertia::render('CtnDocuments/Index', [
            'filters' => Request::all('search', 'trashed'),
            'contactId' => Request::route('contact_id')
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CtnDocuments/Create', []);
    }

    public function store(): RedirectResponse
    {
        Request::validate([
            'name' => ['required', 'max:50'],
            'document' => ['nullable', 'image'],
        ]);
        $contactId = Request::route('contact_id');
        $document = Request::file('document');
        $path = 'documents/' . $contactId;
        $document->storeAs($path, $document->getClientOriginalName());

        $documentEntity = new CtnDocument();
        $documentEntity->name = Request::get('name');
        $documentEntity->path = $path . '/' . $document->getClientOriginalName();
        $documentEntity->save();

        // save with relation to contact

        return Redirect::route('contacts')->with('success', 'Zapisano dokument');
    }
}
