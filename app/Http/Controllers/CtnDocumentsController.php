<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class CtnDocumentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CtnDocuments/Index', [
            'filters' => Request::all('search', 'trashed'),
            'contacts' => Contact::with('funkcja')
                ->orderByName()
                ->paginate(10)
                ->withQueryString()
                ->through(fn($contact) => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'city' => $contact->city,
                    'deleted_at' => $contact->deleted_at,
                    'funkcja' => $contact->funkcja,
                ]),
        ]);
    }
}
