<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildingTimeSheet extends Controller
{
    public function view(): Response
    {
        return Inertia::render('Building/Index.vue');
    }

    public function store(Request $request): bool
    {
        return true;
    }
}
