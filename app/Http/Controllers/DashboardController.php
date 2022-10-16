<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {

//        $organizations = Contact::join('organizations', 'contacts.organization_id', 'organizations.id')->get();
//        dd($organizations);

        return Inertia::render('Dashboard/Index', [
            'filters' => Request::all('search', 'trashed'),
            'organizations' => Organization::join('contacts', 'organizations.kierownikBud_id', '=', 'contacts.id')
                ->where('contacts.user_id', Auth::id())
                ->get(['organizations.id','organizations.nazwaBud','organizations.kierownikBud_id', 'contacts.user_id']),
        ]);
    }
}
