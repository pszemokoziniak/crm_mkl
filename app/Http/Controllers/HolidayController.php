<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Holiday;
use App\Models\ShiftStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function index(Contact $contact)
    {

        return Inertia::render('Holiday/Index', [
            'holiday' => Holiday::with('shiftStatus')
                ->where('contact_id', $contact->id)
                ->orderByDesc('start')
                ->get()
                ->map(fn (Holiday $h) => [
                    'id' => $h->id,
                    'start' => $h->start,
                    'end' => $h->end,
                    'powod' => optional($h->shiftStatus)->title,
                    'kod' => optional($h->shiftStatus)->code,
                ]),
            'userOwner' => Auth::user()->owner,
            'contact' => $contact,
        ]);
    }
    public function create(Contact $contact)
    {
        $contact_id = $contact->id;

        return Inertia('Holiday/Create', [
            'contact_id' => $contact_id,
            'powody' => $this->powody(),
            // Do ostrzeżenia o kolizji: pobyty pracownika na budowach.
            'pobyty' => $this->pobyty($contact),
        ]);
    }
    public function store(StoreHolidayRequest $req)
    {
        $data = new Holiday;
        $data->start = $req->start;
        $data->end = $req->end;
        $data->shift_status_id = $req->shift_status_id;
        $data->contact_id = $req->contact_id;
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
                'shift_status_id' => $holiday->shift_status_id,
            ],
            'contact' => $contact,
            'powody' => $this->powody(),
            'pobyty' => $this->pobyty($contact),
        ]);
    }
    public function update(StoreHolidayRequest $req)
    {
            $data = Holiday::find($req->id);
            $data->start = $req->start;
            $data->end = $req->end;
            $data->shift_status_id = $req->shift_status_id;
            $data->save();

        return Redirect::back()->with('success', 'Element poprawiony.');
    }
    /**
     * Powody nieobecności — istniejący słownik statusów z kart pracy.
     */
    private function powody()
    {
        return ShiftStatus::orderBy('title')->get(['id', 'title', 'code']);
    }

    /**
     * Pobyty na budowach — front ostrzega, gdy nieobecność wchodzi w ich termin.
     */
    private function pobyty(Contact $contact)
    {
        return ContactWorkDate::with('organization')
            ->where('contact_id', $contact->id)
            ->orderBy('start')
            ->get()
            ->map(fn (ContactWorkDate $w) => [
                'nazwaBud' => optional($w->organization)->nazwaBud,
                'start' => $w->start,
                'end' => $w->end,
            ]);
    }

    public function destroy(Holiday $holiday)
    {
        $contact_id = $holiday->contact_id;
        $holiday->delete();

        return Redirect::route('holiday.index', $contact_id)->with('success', 'Element usunięty.');
    }
}
