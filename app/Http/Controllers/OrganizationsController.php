<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\JezykTyp;
use App\Models\KrajTyp;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class OrganizationsController extends Controller
{
    public function index()
    {
        return Inertia::render('Organizations/Index', [
            'filters' => Request::all('search', 'trashed'),
            'organizations' => Auth::user()->account->organizations()
                ->with('krajTyp')
                ->orderBy('name')
                ->filter(Request::only('search', 'trashed'))
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'country' => $organization->krajTyp ? $organization->krajTyp : null,
                    'kierownikBud_id' => $organization->kierownikBud_id,
                    'deleted_at' => $organization->deleted_at,
                ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Organizations/Create');
    }

    public function store()
    {
        Auth::user()->account->organizations()->create(
            Request::validate([
                'name' => ['required', 'max:1200'],
                'nazwaBud' => ['nullable', 'max:1200'],
                'numerBud' => ['nullable', 'max:50'],
                'city' => ['nullable', 'max:2000'],
                'kierownikBud_id' => ['nullable', 'max:50'],
                'zaklad' => ['nullable', 'max:50'],
                'country_id' => ['nullable', 'max:1000'],
                'addressBud' => ['nullable', 'max:25'],
                'addressKwat' => ['nullable', 'max:25'],
            ])
        );

        return Redirect::route('organizations')->with('success', 'Budowa stworzona.');
    }

    public function edit(Organization $organization)
    {
        return Inertia::render('Organizations/Edit', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'nazwaBud' => $organization->nazwaBud,
                'numerBud' => $organization->numerBud,
                'city' => $organization->city,
                'kierownikBud_id' => $organization->kierownikBud_id,
                'zaklad' => $organization->zaklad,
                'country_id' => $organization->country_id,
                'addressBud' => $organization->addressBud,
                'addressKwat' => $organization->addressKwat,
                'deleted_at' => $organization->deleted_at,
                'contacts' => $organization->contacts()->orderByName()->get()->map->only('id', 'name', 'city', 'phone'),
            ],
            'krajTyps' => KrajTyp::all(),
            'kierownikBud' => Contact::where('position', '=', 1)->get(),
        ]);
    }

    public function update(Organization $organization)
    {
        $organization->update(
            Request::validate([
                'name' => ['required', 'max:100'],
                'nazwaBud' => ['nullable', 'max:2250'],
                'numerBud' => ['nullable', 'max:550'],
                'city' => ['nullable', 'max:150'],
                'kierownikBud_id' => ['nullable', 'max:25'],
                'zaklad' => ['nullable', 'max:2000'],
                'country_id' => ['nullable', 'max:25'],
                'addressBud' => ['nullable', 'max:2000'],
                'addressKwat' => ['nullable', 'max:2500'],
            ])
        );

        return Redirect::back()->with('success', 'Budowa poprawiona.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return Redirect::back()->with('success', 'Budowa usunięta.');
    }

    public function restore(Organization $organization)
    {
        $organization->restore();

        return Redirect::back()->with('success', 'Budowa przywrócona.');
    }
}
