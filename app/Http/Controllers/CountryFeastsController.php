<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FeastRequest;
use App\Models\Feast;
use App\Models\KrajTyp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CountryFeastsController extends Controller
{
    public function index(KrajTyp $country): Response
    {
        return Inertia::render('CountryFeasts/Index', [
            'countryId' => $country->id,
            'feasts'    => $country->feasts
        ]);
    }

    public function create(KrajTyp $country): Response
    {
        return Inertia::render('CountryFeasts/Create', [
            'countryId' => $country->id,
        ]);
    }

    public function edit(KrajTyp $country): Response
    {
        return Inertia::render('CountryFeasts/Edit', [
            'countryId' => $country->id,
        ]);
    }

    public function remove(): Response
    {
        return Inertia::render('CountryFeasts/Remove', []);
    }

    public function store(FeastRequest $request, int $country): RedirectResponse
    {
        Feast::create($request->validated())->save();

        return Redirect::route('country_feasts.index', $country);
    }
}
