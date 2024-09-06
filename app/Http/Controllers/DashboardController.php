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
        $contact_id = Contact::where('user_id', Auth::id())->first();
        (empty($contact_id)) ? $contact_id = null : $contact_id = $contact_id->id;

//        $buildings = ContactWorkDate::with('organization')
//            ->with('contact')
//            ->where('contact_work_dates.contact_id', $contact_id)
//            ->get();

        return Inertia::render('Dashboard/Index', [
            'filters' => Request::all('search', 'trashed', 'my'),
            'organizations_user' => Organization::with('kierownik')
                ->with('krajTyp')
                ->where('kierownikBud_id', Contact::where('user_id', Auth::id())->pluck('id')->first())
                ->orWhere('inzynier_id', Contact::where('user_id', Auth::id())->pluck('id')->first())
                ->filter(Request::only('search', 'trashed', 'my'))
                ->paginate(100)
                ->getCollection()
                ->transform(function ($organization) {
                return [
                    'id' => $organization->id,
                    'nazwaBud' => $organization->nazwaBud,
                    'numerBud' => $organization->numerBud,
                    'kierownikBud_id' => $organization->kierownikBud_id,
                    'city' => $organization->city,
                    'country' => $organization->krajTyp ? $organization->krajTyp : null,
                    'workers_count' => ContactWorkDate::where('organization_id', $organization->id)->count(),
                    'kierownik' => $organization->kierownik ? $organization->kierownik : null,
                    'deleted_at' => $organization->deleted_at,
                ];
            }),
            'organizations_other' => Organization::with('kierownik')
                ->with('krajTyp')
                ->where('kierownikBud_id', '<>', Contact::where('user_id', Auth::id())->pluck('id')->first())
                ->orWhere('inzynier_id', Contact::where('user_id', '<>', Auth::id())->pluck('id')->first())
                ->filter(Request::only('search', 'trashed', 'my'))
                ->paginate(100)
                ->getCollection()
                ->transform(function ($organization) {
                    return [
                        'id' => $organization->id,
                        'nazwaBud' => $organization->nazwaBud,
                        'numerBud' => $organization->numerBud,
                        'kierownikBud_id' => $organization->kierownikBud_id,
                        'city' => $organization->city,
                        'country' => $organization->krajTyp ? $organization->krajTyp : null,
                        'workers_count' => ContactWorkDate::where('organization_id', $organization->id)->count(),
                        'kierownik' => $organization->kierownik ? $organization->kierownik : null,
                        'deleted_at' => $organization->deleted_at,
                    ];
                }),
            'organizations_biuro' => Organization::with('kierownik')
                ->with('krajTyp')
                ->filter(Request::only('search', 'trashed', 'my'))
                ->paginate(100)
                ->getCollection()
                ->transform(function ($organization) {
                    return [
                        'id' => $organization->id,
                        'nazwaBud' => $organization->nazwaBud,
                        'numerBud' => $organization->numerBud,
                        'kierownikBud_id' => $organization->kierownikBud_id,
                        'city' => $organization->city,
                        'country' => $organization->krajTyp ? $organization->krajTyp : null,
                        'workers_count' => ContactWorkDate::where('organization_id', $organization->id)->count(),
                        'kierownik' => $organization->kierownik ? $organization->kierownik : null,
                        'deleted_at' => $organization->deleted_at,
                    ];
                }),
            'user_owner' => [Auth::id(), Auth::user()->owner, Contact::where('user_id', Auth::id())->pluck('id')->first()],

//            'buildings' => $buildings,
        ]);
    }

}
