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
        $contact = Contact::where('user_id', Auth::id())->first();
        $contact_id = $contact ? $contact->id : null;

        $now = now()->format('Y-m-d');

        return Inertia::render('Dashboard/Index', [
            'filters' => Request::all('search', 'trashed', 'my'),
            'organizations_user' => Organization::with(['inzynier', 'krajTyp'])
                ->where(function($query) use ($contact_id) {
                    $query->where('kierownikBud_id', $contact_id)
                          ->orWhere('inzynier_id', $contact_id);
                })
                ->filter(Request::only('search', 'trashed', 'my'))
                ->paginate(100)
                ->getCollection()
                ->transform(function ($organization) use ($now) {
                return [
                    'id' => $organization->id,
                    'nazwaBud' => $organization->nazwaBud,
                    'numerBud' => $organization->numerBud,
                    'kierownikBud_id' => $organization->kierownikBud_id,
                    'inzynier_id' => $organization->inzynier_id,
                    'city' => $organization->city,
                    'country' => $organization->krajTyp ? $organization->krajTyp : null,
                    'workers_count' => ContactWorkDate::where('organization_id', $organization->id)
                        ->activeOn($now)
                        ->count(),
                    'inzynier' => $organization->inzynier ? [
                        'id' => $organization->inzynier->id,
                        'first_name' => $organization->inzynier->first_name,
                        'last_name' => $organization->inzynier->last_name,
                    ] : null,
                    'deleted_at' => $organization->deleted_at,
                ];
            }),
            'organizations_other' => Organization::with(['inzynier', 'krajTyp'])
                ->where(function($query) use ($contact_id) {
                    $query->where('kierownikBud_id', '<>', $contact_id)
                          ->where('inzynier_id', '<>', $contact_id);
                })
                ->filter(Request::only('search', 'trashed', 'my'))
                ->paginate(100)
                ->getCollection()
                ->transform(function ($organization) use ($now) {
                    return [
                        'id' => $organization->id,
                        'nazwaBud' => $organization->nazwaBud,
                        'numerBud' => $organization->numerBud,
                        'kierownikBud_id' => $organization->kierownikBud_id,
                        'inzynier_id' => $organization->inzynier_id,
                        'city' => $organization->city,
                        'country' => $organization->krajTyp ? $organization->krajTyp : null,
                        'workers_count' => ContactWorkDate::where('organization_id', $organization->id)
                            ->activeOn($now)
                            ->count(),
                        'inzynier' => $organization->inzynier ? [
                            'id' => $organization->inzynier->id,
                            'first_name' => $organization->inzynier->first_name,
                            'last_name' => $organization->inzynier->last_name,
                        ] : null,
                        'deleted_at' => $organization->deleted_at,
                    ];
                }),
            'organizations_biuro' => Organization::with(['inzynier', 'krajTyp'])
                ->filter(Request::only('search', 'trashed', 'my'))
                ->paginate(100)
                ->getCollection()
                ->transform(function ($organization) use ($now) {
                    return [
                        'id' => $organization->id,
                        'nazwaBud' => $organization->nazwaBud,
                        'numerBud' => $organization->numerBud,
                        'kierownikBud_id' => $organization->kierownikBud_id,
                        'inzynier_id' => $organization->inzynier_id,
                        'city' => $organization->city,
                        'country' => $organization->krajTyp ? $organization->krajTyp : null,
                        'workers_count' => ContactWorkDate::where('organization_id', $organization->id)
                            ->activeOn($now)
                            ->count(),
                        'inzynier' => $organization->inzynier ? [
                            'id' => $organization->inzynier->id,
                            'first_name' => $organization->inzynier->first_name,
                            'last_name' => $organization->inzynier->last_name,
                        ] : null,
                        'deleted_at' => $organization->deleted_at,
                    ];
                }),
            'user_owner' => [Auth::id(), Auth::user()->owner, $contact_id],
        ]);
    }
}
