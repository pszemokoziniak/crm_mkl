<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

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

    public function store(): void
    {
        Request::validate([
            'name' => ['required', 'max:50'],
            'document' => ['nullable', 'image'],
        ]);

        Auth::user()->account->documents()->create([
            'name' => Request::get('name'),
            'document' => 'hardcoded_example_marcin',
        ]);
    }
}
