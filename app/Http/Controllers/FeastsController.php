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

class FeastsController extends Controller
{
    public function index(KrajTyp $country): Response
    {
        return Inertia::render('Feasts/Index', [
            'countryId' => $country->getAttribute('id'),
            'country'   => $country,
            'feasts'    => $country->getAttribute('feasts')
        ]);
    }

    public function create(KrajTyp $country): Response
    {
        return Inertia::render('Feasts/Create', [
            'countryId' => $country->id,
            'country' => $country,
        ]);
    }

    public function edit(KrajTyp $country, Feast $feast): Response
    {
        return Inertia::render('Feasts/Edit', [
            'countryId' => $country->id,
            'country' => $country,
            'feast'     => $feast
        ]);
    }

    public function delete(KrajTyp $country, Feast $feast): RedirectResponse
    {
        $feast->delete();

        return Redirect::route('country_feasts.index', $country->id);
    }

    public function store(FeastRequest $request, int $country): RedirectResponse
    {
        Feast::updateOrCreate(['id' => $request->get('id')], $request->all())->save();

        return Redirect::route('krajTyp.edit', $country);
    }
}
