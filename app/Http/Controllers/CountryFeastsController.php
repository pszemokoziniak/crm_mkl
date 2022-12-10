<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KrajTyp;
use Inertia\Inertia;
use Inertia\Response;

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
    public function store() {}
}
