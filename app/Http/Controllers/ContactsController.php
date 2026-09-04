<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreCustomersRequest;
use App\Models\A1;
use App\Models\Badania;
use App\Models\Bhp;
use App\Models\BuildingTimeSheet;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Jezyk;
use App\Models\Organization;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
use App\Services\StatusPracownika;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class ContactsController extends Controller
{
    public function index(StatusPracownika $statusPracownika)
    {
        return $this->listaOsob($statusPracownika, false, 'Pracownicy', '/contacts');
    }

    /**
     * Kierownictwo — ta sama lista i te same filtry, tylko inny zbiór osób.
     * O tym, kto tu trafia, decyduje znacznik przy stanowisku w Ustawieniach,
     * więc nowe stanowisko da się przypisać do jednej albo drugiej zakładki
     * bez zmiany w kodzie.
     */
    public function kierownicy(StatusPracownika $statusPracownika)
    {
        return $this->listaOsob($statusPracownika, true, 'Kierownicy i inżynierowie', '/kierownicy');
    }

    private function listaOsob(
        StatusPracownika $statusPracownika,
        bool $kierownictwo,
        string $naglowek,
        string $adresListy
    ) {
        return Inertia::render('Contacts/Index', [
            'naglowek' => $naglowek,
            'adresListy' => $adresListy,
            'filters' => Request::all('search', 'trashed', 'status'),
            'contacts' => Contact::with('funkcja')
                ->with('organization')
                // Pobyty i nieobecności z dzisiaj — jednym zapytaniem na całą stronę.
                ->with($statusPracownika->relacjeDoListy())
                // Najnowsze A1 — jednym zapytaniem na stronę, nie na wiersz.
                ->with(['a1' => fn ($query) => $query->orderByDesc('end')])
                ->kierownictwo($kierownictwo)
                ->orderByName()
                ->filter(Request::only('search', 'trashed', 'status'))
                ->paginate(20)
                ->withQueryString()
                ->through(fn ($contact) => [
                    'id' => $contact->id,
                    'name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                    'city' => $contact->city,
                    'deleted_at' => $contact->deleted_at,
                    'funkcja' => $contact->funkcja,
                    'budowa' => $contact->organization,
                    'a1' => $contact->a1->first(),
                    'pracuje' => $statusPracownika->dla($contact),
                ]),
        ]);
    }

    public function create()
    {

        return Inertia::render('Contacts/Create', [
            'organizations' => $this->budowyDoPrzypisania(),
            'accounts' => Auth::user()->account
                ->accounts()
                ->map
                ->only('id', 'name'),
            'funkcjas' => Funkcja::all(),
        ]);
    }

    public function store(StoreCustomersRequest $request)
    {
        Auth::user()->account->contacts()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'birth_date' => $request->birth_date,
            'pesel' => $request->pesel,
            'idCard_number' => $request->idCard_number,
            'idCard_date' => $request->idCard_date,
            'funkcja_id' => $request->funkcja_id,
            'work_start' => $request->work_start,
            'work_end' => $request->work_end,
            'ekuz' => $request->ekuz,
            'miejsce_urodzenia' => $request->miejsce_urodzenia,
            'organization_id' => $request->organization_id,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'photo_path' => $request->file('photo_path') ? $request->file('photo_path')->store('contacts') : null,
            'status_zatrudnienia' => $request->status_zatrudnienia,
        ]);


        return Redirect::route('contacts')->with('success', 'Pracownik stworzony');
    }

    public function edit(Contact $contact, StatusPracownika $statusPracownika)
    {
        // Aktualne i przyszłe przypisania do budów (pobyty jeszcze niezakończone).
        $przypisania = ContactWorkDate::with('organization')
            ->where('contact_id', $contact->id)
            ->notFinished(Carbon::today()->toDateString())
            ->orderBy('start')
            ->get()
            ->map(fn (ContactWorkDate $w) => [
                'id' => $w->id,
                'organization_id' => $w->organization_id,
                'nazwaBud' => optional($w->organization)->nazwaBud,
                'start' => $w->start,
                'end' => $w->end,
            ]);

        $flag = false;
        if (Auth::user()->owner === 3) {
            $flag = true;
        }

        $timeSheets = BuildingTimeSheet::where('contact_id', $contact->id)->get();
        $totalHours = $timeSheets->sum(function ($item) {
            if (!$item->effective_work_time) return 0;
            list($hours, $minutes) = explode(':', $item->effective_work_time);
            return (int)$hours + ((int)$minutes / 60);
        });
        $buildsCount = $timeSheets->pluck('organization_id')->unique()->count();

        // Do ostrzeżenia w okienku potwierdzenia: wszystkie pobyty, także zakończone,
        // bo przypisywać można też wstecz.
        $wszystkiePobyty = ContactWorkDate::with('organization')
            ->where('contact_id', $contact->id)
            ->orderBy('start')
            ->get()
            ->map(fn (ContactWorkDate $w) => [
                'nazwaBud' => optional($w->organization)->nazwaBud,
                'start' => $w->start,
                'end' => $w->end,
            ]);

        // Stanowiska kierownicze mogą być na dwóch budowach naraz — reszta nie.
        $czyKierownictwo = (bool) optional($contact->funkcja)->kierownictwo;

        return Inertia::render('Contacts/Edit', [
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'organization_id' => $contact->organization_id,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'address' => $contact->address,
                'birth_date' => $contact->birth_date,
                'pesel' => $contact->pesel,
                'idCard_number' => $contact->idCard_number,
                'idCard_date' => $contact->idCard_date,
                'funkcja_id' => $contact->funkcja_id,
                'work_start' => $contact->work_start,
                'work_end' => $contact->work_end,
                'ekuz' => $contact->ekuz,
                'miejsce_urodzenia' => $contact->miejsce_urodzenia,
                'photo_path' => $contact->photo_path ? URL::route('image', ['path' => $contact->photo_path, 'w' => 260, 'h' => 260, 'fit' => 'crop', 'fm' => 'jpg']) : null,
                'deleted_at' => $contact->deleted_at,
                'status_zatrudnienia' => $contact->status_zatrudnienia,
            ],
            'organizations' => $this->budowyDoPrzypisania(),
                'accounts' => Auth::user()->account
                ->accounts()
                ->map
                ->only('id', 'name'),
            'funkcjas' => Funkcja::all(),
            'jezyks' => Jezyk::with('jezykTyp')
                ->where('contact_id', $contact->id)
                ->orderByName()
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($jezyk) => [
                    'id' => $jezyk->id,
                    'poziom' => $jezyk->poziom,
                    'jezyk' => $jezyk->jezykTyp ? $jezyk->jezykTyp : null,
                ]),

            'bhp' => Bhp::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'lekarskie' => Badania::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'a1' => A1::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'uprawnienia' => Uprawnienia::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'pbioz' => Pbioz::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'przypisania' => $przypisania,
            'status' => $statusPracownika->dla($contact),
            'wszystkiePobyty' => $wszystkiePobyty,
            'czyKierownictwo' => $czyKierownictwo,
            'flag' => $flag,
            'user_owner' => Auth::user()->owner,
            'stats' => [
                'total_hours' => round($totalHours, 1),
                'builds_count' => $buildsCount,
            ]
        ]);
    }

    /**
     * Przypisanie pracownika do budowy z jego profilu. Usuwanie/przenoszenie
     * odbywa się osobno — w zakładce budowy (edycja dat), świadomie.
     */
    /**
     * Budowy do wyboru przy przypisywaniu pracownika.
     * Bez filtra po koncie: budowy zapisują się z account_id = 0
     * (OrganizationsController@store), a użytkownicy mają account_id = 1,
     * więc filtrowanie po koncie zwracało pustą listę.
     *
     * @return \Illuminate\Support\Collection
     */
    private function budowyDoPrzypisania()
    {
        return Organization::orderBy('nazwaBud')
            ->get(['id', 'name', 'nazwaBud'])
            ->map
            ->only('id', 'name', 'nazwaBud');
    }

    public function przypiszBudowe(Contact $contact)
    {
        $data = Request::validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ], [
            'required' => 'Pole jest wymagane.',
            'after_or_equal' => 'Koniec nie może być przed początkiem.',
        ]);

        // Kolizja: pracownik nie może w tym terminie być już na innej budowie.
        $overlap = ContactWorkDate::with('organization')
            ->where('contact_id', $contact->id)
            ->where('start', '<=', $data['end'])
            ->where(function ($q) use ($data) {
                $q->whereNull('end')->orWhere('end', '>=', $data['start']);
            })
            ->first();

        // Kierownictwo (kierownik, inżynier, koordynator, BHP...) może obsługiwać
        // dwie budowy naraz — decyzja biura. Pozostałych chronimy przed pomyłką
        // w grafiku, bo monter nie będzie w dwóch miejscach jednocześnie.
        if ($overlap && ! optional($contact->funkcja)->kierownictwo) {
            $nazwa = optional($overlap->organization)->nazwaBud ?? 'inna budowa';

            return Redirect::back()->with('error',
                'Pracownik jest już w tym terminie przypisany do: '.$nazwa.
                ' ('.$overlap->start.' – '.$overlap->end.'). Najpierw popraw daty w zakładce tej budowy.');
        }

        ContactWorkDate::create([
            'contact_id' => $contact->id,
            'organization_id' => $data['organization_id'],
            'start' => $data['start'],
            'end' => $data['end'],
        ]);

        return Redirect::back()->with('success', 'Pracownik przypisany do budowy.');
    }

    public function update(Contact $contact, StoreContactRequest $request)
    {
        $data = $request->only('first_name', 'last_name', 'birth_date', 'pesel', 'idCard_number', 'idCard_date', 'funkcja_id', 'work_start',
            'work_end', 'ekuz', 'miejsce_urodzenia', 'organization_id', 'email', 'phone', 'address', 'status_zatrudnienia');

        if ($request->hasFile('photo_path')) {
            $data['photo_path'] = $request->file('photo_path')->store('contacts');
        }

        $contact->update($data);

        // "Zwolniony" + potwierdzona archiwizacja -> przenosimy do archiwum
        // (soft-delete + czyszczenie pobytów, jak przy usuwaniu pracownika).
        if ($request->boolean('archive') && $contact->status_zatrudnienia === 'Zwolniony') {
            ContactWorkDate::where('contact_id', $contact->id)->delete();
            $contact->delete();

            return Redirect::route('contacts')->with('success', 'Pracownik zwolniony i przeniesiony do archiwum.');
        }

        return Redirect::back()->with('success', 'Pracownik poprawiony.');
    }

    public function destroy(Contact $contact)
    {
        // Usuwamy też pobyty pracownika na budowach — inaczej zostają
        // osierocone (blokują archiwizację budowy, zawyżają obsadę w prognozie).
        ContactWorkDate::where('contact_id', $contact->id)->delete();
        $contact->delete();

        return Redirect::back()->with('success', 'Pracownik usunięty.');
    }

    public function restore(Contact $contact)
    {
        $zarchiwizowany = $contact->deleted_at;

        $contact->restore();

        // Do archiwum trafia się przez "Zwolniony", więc przywrócenie bez
        // zdjęcia tego statusu zostawiało pracownika czynnego, ale opisanego
        // jako zwolniony — i wypadał z list, na których powinien być.
        if ($contact->status_zatrudnienia === Contact::STATUS_ZWOLNIONY) {
            $contact->update(['status_zatrudnienia' => Contact::STATUS_AKTYWNY]);
        }

        $this->przywrocPobyty($contact, $zarchiwizowany);

        return Redirect::back()->with('success', 'Pracownik przywrócony.');
    }

    /**
     * Historia pobytów na budowach wraca razem z pracownikiem. Bierzemy
     * tylko te, które zniknęły przy archiwizacji — pobyty skasowane wcześniej,
     * ręcznie w zakładce budowy, mają zostać skasowane.
     */
    private function przywrocPobyty(Contact $contact, ?Carbon $zarchiwizowany): void
    {
        if (! $zarchiwizowany) {
            return;
        }

        // Pobyty kasuje się tuż przed samym pracownikiem, więc znaczniki czasu
        // różnią się o ułamek chwili — stąd minuta zapasu w obie strony.
        ContactWorkDate::onlyTrashed()
            ->where('contact_id', $contact->id)
            ->whereBetween('deleted_at', [
                $zarchiwizowany->copy()->subMinute(),
                $zarchiwizowany->copy()->addMinute(),
            ])
            ->restore();
    }

    public function storePracownik(Request $request, Organization $organization)
    {
        foreach ($request::all() as $item) {
            $data = Contact::find($item);
            $data->organization_id = $organization->id;
            $data->save();

            $data = new ContactWorkDate;
            $data->contact_id = $item;
            $data->organization_id = $organization->id;
            $data->save();
        }


        return Redirect::back()->with('success', 'Pracownik dodany.');
    }

    public function destroyPracownikBudowa(Contact $contact)
    {
//        dd($contact);
        $data = Contact::find($contact->id);
        $data->organization_id = null;
        $data->save();
        return Redirect::back()->with('success', 'Pracownik usunięty.');
    }

    public function history(Contact $contact)
    {
        $history = BuildingTimeSheet::with(['build' => fn ($q) => $q->withTrashed()])
            ->where('contact_id', $contact->id)
            ->orderBy('work_day', 'desc')
            ->get()
            ->groupBy('organization_id')
            ->map(function ($group) {
                // build bywa null (budowa usunięta z bazy) — bez guardu leciał 500.
                return [
                    'organization' => optional($group->first()->build)->nazwaBud ?? '— budowa usunięta —',
                    'start' => $group->min('work_day'),
                    'end' => $group->max('work_day'),
                    'hours' => $group->sum(function ($item) {
                        if (!$item->effective_work_time) return 0;
                        list($hours, $minutes) = explode(':', $item->effective_work_time);

                        return $hours + ($minutes / 60);
                    }),
                ];
            });

        return Inertia::render('Contacts/History', [
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
            ],
            'history' => $history->values(),
        ]);
    }
}
