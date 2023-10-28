<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Http\Requests\StoreTypGodzinyRequest;
use App\Models\ShiftStatus;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ShiftStatusController extends Controller
{
    public function index(): Response
    {
        return Inertia('ShiftStatusTyp/Index', ['ShiftStatusTypes' => ShiftStatus::get()]);
    }

    public function edit(ShiftStatus $shiftStatus): Response
    {
//        dd($shiftStatus);
        return Inertia::render('ShiftStatusTyp/Edit', [
            'shiftStatus' => [
                'id' => $shiftStatus->id,
                'title' => $shiftStatus->title,
                'code' => $shiftStatus->code,
                'deleted_at' => $shiftStatus->deleted_at,
            ],
        ]);
    }

    public function update(ShiftStatus $shiftStatus)
    {
        $shiftStatus->update(Request::validate([
                'title' => ['required', 'max:100'],
                'code' => ['required', 'max:15'],
            ])
        );
        return Redirect::route('shiftStatusTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(ShiftStatus $shiftStatus)
    {
        $shiftStatus->delete();

        return Redirect::route('shiftStatusTyp')->with('success', 'Usunięto.');
    }

    public function restore(ShiftStatus $shiftStatus)
    {
        $shiftStatus->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('ShiftStatusTyp/Create');
    }

    public function store(StoreTypGodzinyRequest $request)
    {
        ShiftStatus::create($request->validated());
        return Redirect::route('shiftStatusTyp')->with('success', 'Zapisano.');
    }
}
