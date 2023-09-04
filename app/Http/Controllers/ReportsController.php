<?php

namespace App\Http\Controllers;

use App\Models\A1;
use App\Models\Badania;
use App\Models\Bhp;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    public function koniecUprawinien(Request $request)
    {

        //zmienne
        // ilość dni po którym uprawnienia znikają z raport koniec uprawnien
        $prevDays = 7;
        $data = array();

        $bhps = BHP::join('contacts', 'bhps.contact_id', '=', 'contacts.id')
            ->join('bhp_typs', 'bhps.bhpTyp_id', '=', 'bhp_typs.id')
            ->where('bhps.end', '>', Carbon::now()->subDays($prevDays))
            ->orderBy('bhps.end')
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'bhp_typs.name', 'bhps.start', 'bhps.end']);

        foreach ($bhps as $item) {
            $data[] = array('client_id' => $item->id, 'last_name' => $item->last_name.' '.$item->first_name, 'name' => 'BHP / '.$item->name, 'start' => $item->start, 'end' => $item->end);
        }

        $a1s = A1::join('contacts', 'a1_s.contact_id', '=', 'contacts.id')
            ->where('a1_s.end', '>', Carbon::now()->subDays($prevDays))
            ->orderBy('a1_s.end')
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'a1_s.start', 'a1_s.end']);

        foreach ($a1s as $item) {
            $data[] = array('client_id' => $item->id, 'last_name' => $item->last_name.' '.$item->first_name, 'name' => 'A1', 'start' => $item->start, 'end' => $item->end);
        }

        $badania = Badania::join('contacts', 'badanias.contact_id', '=', 'contacts.id')
            ->join('badania_typs', 'badanias.badaniaTyp_id', '=', 'badania_typs.id')
            ->where('badanias.end', '>', Carbon::now()->subDays($prevDays))
            ->orderBy('badanias.end')
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'badania_typs.name', 'badanias.start', 'badanias.end']);

        foreach ($badania as $item) {
            $data[] = array('client_id' => $item->id, 'last_name' => $item->last_name.' '.$item->first_name, 'name' => 'Lekarskie / '.$item->name, 'start' => $item->start, 'end' => $item->end);
        }

        $uprawnienia = Uprawnienia::join('contacts', 'uprawnienias.contact_id', '=', 'contacts.id')
            ->join('uprawnienia_typs', 'uprawnienias.uprawnieniaTyp_id', '=', 'uprawnienia_typs.id')
            ->where('uprawnienias.end', '>', Carbon::now()->subDays($prevDays))
            ->orderBy('uprawnienias.end')
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'uprawnienia_typs.name', 'uprawnienias.start', 'uprawnienias.end']);

        foreach ($uprawnienia as $item) {
            $data[] = array('client_id' => $item->id, 'last_name' => $item->last_name.' '.$item->first_name, 'name' => 'Uprawnienia / '.$item->name, 'start' => $item->start, 'end' => $item->end);
        }

        $pbioz = Pbioz::join('contacts', 'pbiozs.contact_id', '=', 'contacts.id')
            ->where('pbiozs.end', '>', Carbon::now()->subDays($prevDays))
            ->orderBy('pbiozs.end')
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'pbiozs.name', 'pbiozs.start', 'pbiozs.end']);

        foreach ($pbioz as $item) {
            $data[] = array('client_id' => $item->id, 'last_name' => $item->last_name.' '.$item->first_name, 'name' => 'PBIOZ / '.$item->name, 'start' => $item->start, 'end' => $item->end);
        }

        if ($data) {
            $all = collect($data)->sortBy('end')->values();
        } else {
            $all = array();
        }
        if (Request::all()) {
            $search = strtolower(collect(Request::only('search'))->first());
            $collectionWhereLike = function ($collection, $key, $search) {
                $filtered = $collection->filter(fn ($item) => Str::contains(strtolower($item['last_name']), $search) || Str::contains(strtolower($item['name']), $search));
                $reIndexed = array_values($filtered->toArray());
                return collect($reIndexed);
            };

            $data = $collectionWhereLike($all, 'last_name', $search);
        }

        return Inertia::render('Reports/TerminUprawnien', [
            'filters' => Request::all('search'),
            'data' => $data,
        ]);
    }

}
