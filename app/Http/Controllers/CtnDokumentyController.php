<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CtnDokumentyController extends Controller
{
    public function index()
    {
        return Inertia::render('CtnDokumanty/Index', [

        ]);
    }
}
