<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Models\Contact;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function index(Contact $contact)
    {

        return Inertia::render('Holiday/Index', [
            'holiday' => Holiday::where('contact_id', $contact->id)->get(),
            'contact' => $contact,
        ]);
    }
    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        return Inertia('Holiday/Create', compact('contact_id'));
    }
    public function store(StoreHolidayRequest $req)
    {
        $data = new Holiday;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$req->contact_id;
        $data->save();
        return Redirect::route('holiday.index', $req->contact_id)->with('success', 'Zapisano.');
    }
    public function edit(Contact $contact, Holiday $holiday)
    {
        return Inertia::render('Holiday/Edit', [
            'holiday' => [
                'id' => $holiday->id,
                'start' => $holiday->start,
                'end' => $holiday->end,
            ],
            'contact' => $contact
        ]);
    }
    public function update(StoreHolidayRequest $req)
    {
            $data = Holiday::find($req->id);
            $data->start = $req->start;
            $data->end = $req->end;
            $data->save();

        return Redirect::back()->with('success', 'Element poprawiony.');
    }
    public function destroy(Holiday $holiday)
    {
        $contact_id = $holiday->contact_id;
        $holiday->delete();

        return Redirect::route('holiday.index', $contact_id)->with('success', 'Element usunięty.');
    }
}
