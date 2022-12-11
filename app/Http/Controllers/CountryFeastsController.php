<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KrajTyp;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Request;

class CountryFeastsController extends Controller
{
    public function index(KrajTyp $country): Response
    {
        return Inertia::render('CountryFeasts/Index', [
            'feasts' => $country->feasts()
        ]);
    }
    public function create(): Response
    {
        return Inertia::render('CountryFeasts/Create', []);
    }
    public function edit(): Response
    {
        return Inertia::render('CountryFeasts/Edit', []);
    }
    public function remove(): Response
    {
        return Inertia::render('CountryFeasts/Remove', []);
    }
    public function store(Request $request, $country)
    {
        // @TODO to implement

        return Redirect::route('country_feasts.index', $country);
    }
}
