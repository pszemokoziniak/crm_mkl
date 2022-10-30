<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    public function koniecUprawinien()
    {
        return Inertia::render('Reports/Index');
    }

}
