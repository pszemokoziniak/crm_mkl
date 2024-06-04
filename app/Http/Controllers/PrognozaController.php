<?php

namespace App\Http\Controllers;

use App\Models\Prognoza;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PrognozaController extends Controller
{
    public function index()
    {
        $years = array();
        $currentYear = Carbon::now();

        for ($i = 0; $i < 5; $i++) {
            $years[] = $currentYear->copy()->addYears($i)->year;
        }


//        $data = Prognoza::where('week', $week)->where('year', $year)->get();
        return Inertia('Prognoza/Index', compact('years'));
    }

    public function edit(BhpTyp $bhpTyp)
    {
        return Inertia::render('BhpTyp/Edit', [
            'bhp' => [
                'id' => $bhpTyp->id,
                'name' => $bhpTyp->name,
                'deleted_at' => $bhpTyp->deleted_at,
            ],
        ]);
    }

    public function update(BhpTyp $bhpTyp)
    {
        $bhpTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
            ])
        );
        return Redirect::route('bhpTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(BhpTyp $bhpTyp)
    {
        $bhpTyp->delete();

        return Redirect::route('bhpTyp')->with('success', 'Usunięto.');
    }

    public function restore(BadaniaTyp $badaniaTyp)
    {
        $badaniaTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('BhpTyp/Create');
    }

    public function store(StorePosRequest $req)
    {
        BhpTyp::create($req->validated());
        return Redirect::route('bhpTyp')->with('success', 'Zapisano.');
    }
}
