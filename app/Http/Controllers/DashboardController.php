<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $contact_id = Contact::where('user_id', Auth::id())->pluck('id');
        $contact_id = (!empty($contact_id))?$contact_id:null;
        $buildings = ContactWorkDate::with('organization')
            ->with('contact')
            ->where('contact_work_dates.contact_id', $contact_id)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'filters' => Request::all('search', 'trashed'),
            'organizations' => Organization::join('contacts', 'organizations.kierownikBud_id', '=', 'contacts.id')
                ->where('contacts.user_id', Auth::id())
                ->get(['organizations.id','organizations.nazwaBud','organizations.kierownikBud_id', 'contacts.user_id']),
            'buildings' => $buildings,
        ]);
    }

}
