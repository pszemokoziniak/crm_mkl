<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Uprawnienie;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Koszt;
use App\Models\KosztOsoba;
use App\Models\Organization;
use App\Models\TypKosztu;
use App\Services\BrakKursuException;
use App\Services\Kursy;
use App\Services\PodzialKosztow;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Koszty podróży i kwater: zakładka "Koszty" na budowie i w karcie
 * pracownika. Wpis ma kwotę w walucie i zamrożoną kwotę w PLN po kursie
 * NBP z dnia kosztu; pokój ma miejsca i zakwaterowanych.
 */
class KosztyController extends Controller
{
    private const KATALOG = 'koszty';

    public function __construct(private Kursy $kursy, private PodzialKosztow $podzial)
    {
    }

    public function budowa(Organization $organization): Response
    {
        $miesiac = $this->miesiac();

        $koszty = Koszt::with(['typ', 'contact', 'osoby.contact', 'user'])
            ->where('organization_id', $organization->id)
            ->wMiesiacu($miesiac)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Koszty/Budowa', [
            'build' => $organization->id,
            'buildDetails' => $organization,
            'miesiac' => $miesiac,
            'koszty' => $koszty->map(fn (Koszt $k) => $this->wiersz($k)),
            'sumy' => $this->sumy($koszty),
            'podzial' => $this->podzial->dlaBudowy($organization, $miesiac),
            'typy' => $this->typy(),
            'waluty' => Kursy::WALUTY,
            'pracownicy' => $this->pracownicyBudowy($organization, $miesiac, $koszty),
            'moze_edytowac' => Auth::user()->moze(Uprawnienie::KOSZTY_OBSLUGA),
        ]);
    }

    public function pracownik(Contact $contact): Response
    {
        $miesiac = $this->miesiac();

        $wlasne = Koszt::with(['typ', 'organization', 'osoby.contact', 'user'])
            ->where('contact_id', $contact->id)
            ->wMiesiacu($miesiac)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get();

        $udzialy = $this->podzial->dlaPracownika($contact, $miesiac);
        [$mOd, $mDo] = Koszt::zakresMiesiaca($miesiac);

        $budowy = Organization::withTrashed()
            ->whereIn('id', ContactWorkDate::where('contact_id', $contact->id)
                ->where('start', '<=', $mDo)
                ->where(fn ($q) => $q->whereNull('end')->orWhere('end', '>=', $mOd))
                ->pluck('organization_id'))
            ->orderBy('nazwaBud')
            ->get(['id', 'nazwaBud'])
            ->map(fn (Organization $o) => ['id' => $o->id, 'nazwa' => $o->nazwaBud]);

        return Inertia::render('Koszty/Pracownik', [
            'pracownik' => $this->danePracownika($contact),
            'contact' => ['id' => $contact->id],
            'userOwner' => Auth::user()->owner,
            'miesiac' => $miesiac,
            'koszty' => $wlasne->map(fn (Koszt $k) => $this->wiersz($k)),
            'sumy' => $this->sumy($wlasne),
            'udzialy' => $udzialy,
            'suma_udzialow' => round($udzialy->sum('kwota_pln'), 2),
            // Pokoju nie zakłada się z karty osoby — to koszt budowy.
            'typy' => $this->typy()->where('nocleg', false)->values(),
            'waluty' => Kursy::WALUTY,
            'budowy' => $budowy,
            'moze_edytowac' => Auth::user()->moze(Uprawnienie::KOSZTY_OBSLUGA),
        ]);
    }

    public function storeDlaBudowy(Organization $organization): RedirectResponse
    {
        return $this->zapisz(null, ['organization_id' => $organization->id]);
    }

    public function storeDlaPracownika(Contact $contact): RedirectResponse
    {
        return $this->zapisz(null, ['contact_id' => $contact->id]);
    }

    public function update(Koszt $koszt): RedirectResponse
    {
        return $this->zapisz($koszt, []);
    }

    public function destroy(Koszt $koszt): RedirectResponse
    {
        $koszt->delete();

        return Redirect::back()->with('success', 'Koszt usunięty.');
    }

    /**
     * Osoby na koszcie: zakwaterowani w pokoju (z datami, pilnujemy miejsc
     * i tego, żeby nikt nie spał w dwóch pokojach naraz) albo wskazani
     * uczestnicy dzielonego kosztu.
     */
    public function osoby(Koszt $koszt): RedirectResponse
    {
        $koszt->load(['typ', 'osoby']);
        $nocleg = $koszt->jestNoclegiem();

        $dane = Validator::make(Request::all(), [
            'osoby' => ['present', 'array', 'max:100'],
            'osoby.*.contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'osoby.*.od' => [$nocleg ? 'required' : 'nullable', 'date'],
            'osoby.*.do' => [$nocleg ? 'required' : 'nullable', 'date', 'after_or_equal:osoby.*.od'],
        ], [
            'osoby.*.od.required' => 'Podaj, od kiedy osoba mieszka w pokoju.',
            'osoby.*.do.required' => 'Podaj, do kiedy osoba mieszka w pokoju.',
            'osoby.*.do.after_or_equal' => 'Data „do” nie może być przed „od”.',
        ])->validate();

        $osoby = collect($dane['osoby']);

        if ($nocleg) {
            if ($blad = $this->bladZakwaterowania($koszt, $osoby)) {
                return Redirect::back()->withErrors(['osoby' => $blad]);
            }
        } else {
            $osoby = $osoby->map(fn ($o) => ['contact_id' => $o['contact_id'], 'od' => null, 'do' => null])->unique('contact_id');
        }

        $koszt->osoby()->delete();
        $koszt->osoby()->createMany($osoby->values()->all());

        return Redirect::back()->with('success', $nocleg ? 'Zakwaterowanie zapisane.' : 'Osoby zapisane.');
    }

    public function plik(Koszt $koszt): BinaryFileResponse
    {
        $this->sprawdzZakres($koszt);

        abort_if(! $koszt->plik_sciezka || ! Storage::exists($koszt->plik_sciezka), 404);

        return response()->file(Storage::path($koszt->plik_sciezka));
    }

    // --- zapis -------------------------------------------------------------

    private function zapisz(?Koszt $koszt, array $zTrasy): RedirectResponse
    {
        $dane = Validator::make(array_merge(Request::all(), $zTrasy), [
            'typ_kosztu_id' => ['required', 'integer', Rule::exists('typy_kosztow', 'id')->whereNull('deleted_at')],
            'data' => ['required', 'date'],
            'kwota' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'waluta' => ['required', Rule::in(Kursy::WALUTY)],
            'kurs_reczny' => ['nullable', 'boolean'],
            'kurs' => ['nullable', 'numeric', 'gt:0'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'dzielony' => ['nullable', 'boolean'],
            'od' => ['nullable', 'date'],
            'do' => ['nullable', 'date', 'after_or_equal:od'],
            'miejsc' => ['nullable', 'integer', 'min:1', 'max:200'],
            'opis' => ['nullable', 'string', 'max:500'],
            'plik' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ], [
            'kwota.gt' => 'Kwota musi być większa od zera.',
            'plik.mimes' => 'Skan może być zdjęciem (jpg, png) albo plikiem PDF.',
            'plik.max' => 'Plik może mieć najwyżej 10 MB.',
            'do.after_or_equal' => 'Data „do” nie może być przed „od”.',
        ])->validate();

        if (empty($dane['organization_id']) && empty($dane['contact_id'])) {
            return Redirect::back()->withErrors(['contact_id' => 'Koszt musi być przypięty do pracownika albo do budowy.']);
        }

        $typ = TypKosztu::findOrFail($dane['typ_kosztu_id']);

        if ($typ->nocleg) {
            if (empty($dane['od']) || empty($dane['do']) || empty($dane['miejsc'])) {
                return Redirect::back()->withErrors(['od' => 'Pokój wymaga okresu od–do i liczby miejsc.']);
            }
            if (! empty($dane['contact_id'])) {
                return Redirect::back()->withErrors(['contact_id' => 'Pokój to koszt budowy — ludzi przypisuje się do niego niżej, nie w polu pracownika.']);
            }
        }

        $data = Carbon::parse($dane['data'])->toDateString();
        $kursReczny = (bool) ($dane['kurs_reczny'] ?? false) && ! empty($dane['kurs']);

        if ($dane['waluta'] === 'PLN') {
            $kurs = 1.0;
            $kursReczny = false;
        } elseif ($kursReczny) {
            $kurs = (float) $dane['kurs'];
        } else {
            try {
                $kurs = $this->kursy->kurs($dane['waluta'], $data)['kurs'];
            } catch (BrakKursuException $e) {
                return Redirect::back()->withErrors([
                    'kurs' => "Nie udało się pobrać kursu NBP dla {$dane['waluta']} z {$data}. Wpisz kurs ręcznie.",
                ]);
            }
        }

        $atrybuty = [
            'typ_kosztu_id' => $typ->id,
            'organization_id' => $dane['organization_id'] ?? null,
            'contact_id' => $dane['contact_id'] ?? null,
            'data' => $data,
            'kwota' => round((float) $dane['kwota'], 2),
            'waluta' => $dane['waluta'],
            'kurs' => $kurs,
            'kurs_reczny' => $kursReczny,
            'kwota_pln' => round((float) $dane['kwota'] * $kurs, 2),
            'opis' => $dane['opis'] ?? null,
            'od' => $typ->nocleg ? $dane['od'] : null,
            'do' => $typ->nocleg ? $dane['do'] : null,
            'miejsc' => $typ->nocleg ? (int) $dane['miejsc'] : null,
            // Dzielić można tylko koszt budowy bez osoby; dla pokoju zawsze.
            'dzielony' => empty($dane['contact_id']) && ! empty($dane['organization_id'])
                && ($typ->nocleg || (bool) ($dane['dzielony'] ?? $typ->dzielony)),
        ];

        if ($koszt) {
            $koszt->update($atrybuty);
        } else {
            $koszt = Koszt::create($atrybuty + ['user_id' => Auth::id()]);
        }

        if (Request::hasFile('plik')) {
            $plik = Request::file('plik');
            $nazwa = Str::random(40).'.'.strtolower($plik->getClientOriginalExtension() ?: 'bin');
            $plik->storeAs(self::KATALOG.'/'.$koszt->id, $nazwa);
            $koszt->update([
                'plik_sciezka' => self::KATALOG.'/'.$koszt->id.'/'.$nazwa,
                'plik_nazwa' => $plik->getClientOriginalName(),
            ]);
        }

        return Redirect::back()->with('success', $koszt->wasRecentlyCreated ? 'Koszt zapisany.' : 'Koszt poprawiony.');
    }

    /** Za dużo osób na jeden dzień albo ktoś już śpi w innym pokoju — komunikat, inaczej null. */
    private function bladZakwaterowania(Koszt $koszt, Collection $osoby): ?string
    {
        $pOd = $koszt->od?->toDateString();
        $pDo = $koszt->do?->toDateString();

        foreach ($osoby as $o) {
            if ($o['od'] < $pOd || $o['do'] > $pDo) {
                return "Zakwaterowanie musi mieścić się w okresie pokoju ({$pOd} – {$pDo}).";
            }
        }

        // Liczba osób każdego dnia ≤ miejsc.
        $naDzien = [];
        foreach ($osoby as $o) {
            for ($d = Carbon::parse($o['od']); $d->lte(Carbon::parse($o['do'])); $d->addDay()) {
                $naDzien[$d->toDateString()] = ($naDzien[$d->toDateString()] ?? 0) + 1;
            }
        }
        foreach ($naDzien as $dzien => $ile) {
            if ($ile > (int) $koszt->miejsc) {
                return "W dniu {$dzien} w pokoju byłoby {$ile} osób, a miejsc jest {$koszt->miejsc}.";
            }
        }

        // Ta sama osoba w innym pokoju w tym samym czasie.
        $inne = KosztOsoba::whereIn('contact_id', $osoby->pluck('contact_id'))
            ->where('koszt_id', '!=', $koszt->id)
            ->whereHas('koszt', fn ($q) => $q->whereNull('deleted_at')->whereHas('typ', fn ($t) => $t->where('nocleg', true)))
            ->with('contact:id,first_name,last_name', 'koszt:id,opis')
            ->get();

        foreach ($osoby as $o) {
            foreach ($inne->where('contact_id', $o['contact_id']) as $z) {
                if ($z->od && $z->do && $z->od->toDateString() <= $o['do'] && $z->do->toDateString() >= $o['od']) {
                    $kto = trim(optional($z->contact)->last_name.' '.optional($z->contact)->first_name);

                    return "{$kto} mieszka już w innym pokoju od {$z->od->toDateString()} do {$z->do->toDateString()}.";
                }
            }
        }

        return null;
    }

    // --- dane do widoków ---------------------------------------------------

    private function miesiac(): string
    {
        $m = (string) Request::input('miesiac', '');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m) ? $m : Carbon::today()->format('Y-m');
    }

    private function typy(): Collection
    {
        return TypKosztu::orderBy('nazwa')->get()
            ->map(fn (TypKosztu $t) => ['id' => $t->id, 'nazwa' => $t->nazwa, 'dzielony' => $t->dzielony, 'nocleg' => $t->nocleg]);
    }

    /** Ludzie z pobytem na budowie w tym miesiącu plus ci, którzy są już na kosztach. */
    private function pracownicyBudowy(Organization $organization, string $miesiac, Collection $koszty): Collection
    {
        [$mOd, $mDo] = Koszt::zakresMiesiaca($miesiac);

        $ids = ContactWorkDate::where('organization_id', $organization->id)
            ->where('start', '<=', $mDo)
            ->where(fn ($q) => $q->whereNull('end')->orWhere('end', '>=', $mOd))
            ->pluck('contact_id')
            ->merge($koszty->pluck('contact_id'))
            ->merge($koszty->flatMap(fn (Koszt $k) => $k->osoby->pluck('contact_id')))
            ->filter()
            ->unique();

        return Contact::withTrashed()
            ->whereIn('id', $ids)
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Contact $c) => ['id' => $c->id, 'nazwa' => trim($c->last_name.' '.$c->first_name)])
            ->values();
    }

    /** @return array{pln: float, waluty: array<string, float>} */
    private function sumy(Collection $koszty): array
    {
        return [
            'pln' => round($koszty->sum('kwota_pln'), 2),
            'waluty' => $koszty->groupBy('waluta')->map(fn ($g) => round($g->sum('kwota'), 2))->sortKeys()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function wiersz(Koszt $k): array
    {
        return [
            'id' => $k->id,
            'data' => $k->data?->format('Y-m-d'),
            'typ_kosztu_id' => $k->typ_kosztu_id,
            'typ' => optional($k->typ)->nazwa,
            'nocleg' => $k->jestNoclegiem(),
            'opis' => $k->opis,
            'kwota' => $k->kwota,
            'waluta' => $k->waluta,
            'kurs' => $k->kurs,
            'kurs_reczny' => $k->kurs_reczny,
            'kwota_pln' => $k->kwota_pln,
            'dzielony' => $k->dzielony,
            'od' => $k->od?->format('Y-m-d'),
            'do' => $k->do?->format('Y-m-d'),
            'miejsc' => $k->miejsc,
            'organization_id' => $k->organization_id,
            'budowa' => optional($k->organization)->nazwaBud,
            'contact_id' => $k->contact_id,
            'pracownik' => $k->contact ? trim($k->contact->last_name.' '.$k->contact->first_name) : null,
            'osoby' => $k->osoby->map(fn (KosztOsoba $o) => [
                'contact_id' => $o->contact_id,
                'nazwa' => $o->contact ? trim($o->contact->last_name.' '.$o->contact->first_name) : '—',
                'od' => $o->od?->format('Y-m-d'),
                'do' => $o->do?->format('Y-m-d'),
            ])->values(),
            'plik' => $k->plik_sciezka ? '/koszty/'.$k->id.'/plik' : null,
            'plik_nazwa' => $k->plik_nazwa,
            'wpisal' => $k->user ? trim($k->user->first_name.' '.$k->user->last_name) : null,
        ];
    }

    /** Trasy /koszty/{koszt} nie mają budowy w adresie — zakres sprawdzamy sami. */
    private function sprawdzZakres(Koszt $koszt): void
    {
        $user = Auth::user();

        if (! $user->prowadziBudowy()) {
            return;
        }

        $wolno = ($koszt->organization_id && $user->can('view', $koszt->organization))
            || ($koszt->contact_id && $user->can('view', $koszt->contact));

        abort_unless($wolno, 403, 'Nie masz uprawnień do tego kosztu.');
    }
}
