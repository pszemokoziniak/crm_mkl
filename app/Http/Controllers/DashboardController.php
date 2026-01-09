<?php

namespace App\Http\Controllers;

use App\Models\Badania;
use App\Models\Bhp;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
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
        $in30Days = now()->addDays(30)->format('Y-m-d');

        $expiringItems = collect();

        if (Auth::user()->owner === 1 || Auth::user()->owner === 2) {
            // Uprawnienia
            $uprawnienia = Uprawnienia::with(['uprawnieniaTyp'])
                ->join('contacts', 'uprawnienias.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('uprawnienias.*')
                ->get()
                ->map(fn($item) => $this->mapExpiringItem($item, 'Uprawnienia', $item->uprawnieniaTyp->name ?? 'Brak typu', $now));

            // Badania
            $badania = Badania::with(['badaniaTyp'])
                ->join('contacts', 'badanias.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('badanias.*')
                ->get()
                ->map(fn($item) => $this->mapExpiringItem($item, 'Badania lekarskie', $item->badaniaTyp->name ?? 'Brak typu', $now));

            // BHP
            $bhp = Bhp::with(['bhpTyp'])
                ->join('contacts', 'bhps.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('bhps.*')
                ->get()
                ->map(fn($item) => $this->mapExpiringItem($item, 'Szkolenie BHP', $item->bhpTyp->name ?? 'Brak typu', $now));

            // PBIOZ
            $pbioz = Pbioz::join('contacts', 'pbiozs.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('pbiozs.*')
                ->get()
                ->map(fn($item) => $this->mapExpiringItem($item, 'PBIOZ', 'PBIOZ', $now));

            $expiringItems = $uprawnienia->concat($badania)->concat($bhp)->concat($pbioz)->sortBy('end')->values();
        }

        return Inertia::render('Dashboard/Index', [
            'filters' => Request::all('search', 'trashed', 'my'),
            'expiring_items' => $expiringItems,
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
                ->whereHas('contactWorkDates', function ($query) use ($now) {
                    $query->activeOn($now);
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
            'user_owner' => [Auth::id(), Auth::user()->owner, $contact_id],
        ]);
    }

    private function mapExpiringItem($item, $category, $type, $now)
    {
        $contact = Contact::find($item->contact_id);
        $currentWork = ContactWorkDate::with('organization')
            ->where('contact_id', $item->contact_id)
            ->activeOn($now)
            ->first();

        return [
            'id' => $item->id,
            'end' => $item->end,
            'category' => $category,
            'type' => $type,
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
            ],
            'organization' => $currentWork && $currentWork->organization ? [
                'id' => $currentWork->organization->id,
                'nazwaBud' => $currentWork->organization->nazwaBud,
            ] : null,
        ];
    }
}
