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
        $user = Auth::user();
        $contact = Contact::where('user_id', $user->id)
            ->orWhere(function($query) use ($user) {
                $query->where('first_name', $user->first_name)
                      ->where('last_name', $user->last_name);
            })
            ->first();

        $contact_id = $contact ? $contact->id : null;

        $now = now()->format('Y-m-d');
        $in30Days = now()->addDays(30)->format('Y-m-d');

        $expiringItems = collect();
        $myOrgIds = collect();

        if ($user->owner === 3) {
            $myOrgIds = Organization::where(function ($query) use ($contact_id) {
                if ($contact_id) {
                    $query->where('kierownikBud_id', $contact_id)
                        ->orWhere('inzynier_id', $contact_id)
                        ->orWhereHas('contactWorkDates', function ($q) use ($contact_id) {
                            $q->where('contact_id', $contact_id);
                        });
                } else {
                    $query->whereRaw('1 = 0');
                }
            })->pluck('id');
        }

        if ($user->owner === 1 || $user->owner === 2 || $user->owner === 3) {
            // Uprawnienia
            $uprawnieniaQuery = Uprawnienia::with(['uprawnieniaTyp'])
                ->join('contacts', 'uprawnienias.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('uprawnienias.*');

            // Badania
            $badaniaQuery = Badania::with(['badaniaTyp'])
                ->join('contacts', 'badanias.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('badanias.*');

            // BHP
            $bhpQuery = Bhp::with(['bhpTyp'])
                ->join('contacts', 'bhps.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('bhps.*');

            // PBIOZ
            $pbiozQuery = Pbioz::join('contacts', 'pbiozs.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('pbiozs.*');

            // Filtrowanie pracowników, którzy są OBECNIE na budowie
            $filterByActiveWorkers = function($query) use ($myOrgIds, $user, $now) {
                $query->whereIn('contacts.id', function($q) use ($myOrgIds, $user, $now) {
                    $q->select('contact_id')
                      ->from('contact_work_dates')
                      ->whereDate('start', '<=', $now)
                      ->where(function ($q2) use ($now) {
                          $q2->whereNull('end')->orWhereDate('end', '>=', $now);
                      });

                    if ($user->owner === 3) {
                        $q->whereIn('organization_id', $myOrgIds);
                    }
                });
            };

            $filterByActiveWorkers($uprawnieniaQuery);
            $filterByActiveWorkers($badaniaQuery);
            $filterByActiveWorkers($bhpQuery);
            $filterByActiveWorkers($pbiozQuery);

            $uprawnienia = $uprawnieniaQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'Uprawnienia', $item->uprawnieniaTyp->name ?? 'Brak typu', $now));
            $badania = $badaniaQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'Badania lekarskie', $item->badaniaTyp->name ?? 'Brak typu', $now));
            $bhp = $bhpQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'Szkolenie BHP', $item->bhpTyp->name ?? 'Brak typu', $now));
            $pbioz = $pbiozQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'PBIOZ', 'PBIOZ', $now));

            $expiringItems = $uprawnienia->concat($badania)->concat($bhp)->concat($pbioz)->sortBy('end')->values();
        }

        $organizations_user = collect();
        $organizations_biuro = collect();

        if ($user->owner === 3) {
            $organizations_user = Organization::with(['inzynier', 'krajTyp'])
                ->addSelect([
                    'inzynierowie_names' => ContactWorkDate::query()
                        ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                        ->selectRaw(
                            "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                         ORDER BY contacts.last_name SEPARATOR ', ')"
                        )
                        ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                        ->where('contacts.funkcja_id', 6)
                        ->activeOn($now),
                ])
                ->whereIn('id', $myOrgIds)
                ->filter(Request::only('search', 'trashed', 'my'))
                ->get()
                ->transform(fn($org) => $this->transformOrganization($org, $now));
        } else {
            $organizations_biuro = Organization::with(['inzynier', 'krajTyp'])
                ->addSelect([
                    'inzynierowie_names' => ContactWorkDate::query()
                        ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                        ->selectRaw(
                            "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                         ORDER BY contacts.last_name SEPARATOR ', ')"
                        )
                        ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                        ->where('contacts.funkcja_id', 6)
                        ->activeOn($now),
                ])
                ->whereHas('contactWorkDates', function ($query) use ($now) {
                    $query->activeOn($now);
                })
                ->filter(Request::only('search', 'trashed', 'my'))
                ->paginate(100)
                ->getCollection()
                ->transform(fn($org) => $this->transformOrganization($org, $now));
        }

        return Inertia::render('Dashboard/Index', [
            'filters' => Request::all('search', 'trashed', 'my'),
            'expiring_items' => $expiringItems,
            'organizations_user' => $organizations_user,
            'organizations_biuro' => $organizations_biuro,
            'user_owner' => [$user->id, $user->owner, $contact_id],
        ]);
    }

    private function transformOrganization($organization, $now)
    {
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
            'inzynier_name' => $organization->inzynierowie_names,
            'inzynier' => $organization->inzynier ? [
                'id' => $organization->inzynier->id,
                'first_name' => $organization->inzynier->first_name,
                'last_name' => $organization->inzynier->last_name,
            ] : null,
            'deleted_at' => $organization->deleted_at,
        ];
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
