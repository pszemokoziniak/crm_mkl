<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Uprawnienie;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use Illuminate\Support\Facades\DB;
use App\Models\ShiftStatus;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZgloszenieKierownika;
use App\Services\PowiadomieniaKadr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Zgłoszenia od kierowników do kadr. Kierownik zgłasza z zakładki
 * Pracownicy swojej budowy; kadry obsługują na ekranie Kadry.
 */
class ZgloszeniaKierownikowController extends Controller
{
    private const KATALOG = 'zgloszenia';

    public function store(Organization $organization): RedirectResponse
    {
        $dane = Request::validate([
            'contact_id' => ['required', 'integer'],
            'rodzaj' => ['required', Rule::in(array_keys(ZgloszenieKierownika::RODZAJE))],
            'dokument' => ['nullable', 'required_if:rodzaj,'.ZgloszenieKierownika::RODZAJ_DOKUMENT, Rule::in(array_keys(ZgloszenieKierownika::DOKUMENTY))],
            'od' => ['nullable', 'date'],
            'do' => ['nullable', 'date', 'after_or_equal:od'],
            'uwaga' => ['nullable', 'string', 'max:2000'],
            'plik' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ], [
            'do.after_or_equal' => 'Data "do" nie może być przed datą "od".',
            'dokument.required_if' => 'Wybierz, jakiego dokumentu brakuje.',
            'plik.mimes' => 'Skan może być zdjęciem (jpg, png) albo plikiem PDF.',
            'plik.max' => 'Plik może mieć najwyżej 10 MB.',
        ]);

        $user = Auth::user();
        $contact = Contact::withTrashed()->findOrFail((int) $dane['contact_id']);

        // Zgłasza się tylko o kimś, kto jest albo był na TEJ budowie — i kogo
        // zgłaszający w ogóle może oglądać (kierownik: swoi ludzie).
        $naBudowie = ContactWorkDate::where('contact_id', $contact->id)
            ->where('organization_id', $organization->id)
            ->exists();
        abort_unless($naBudowie && $user->can('view', $contact), 403, 'Ten pracownik nie jest na tej budowie.');

        $zgloszenie = new ZgloszenieKierownika();
        $zgloszenie->forceFill([
            'contact_id' => $contact->id,
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'rodzaj' => $dane['rodzaj'],
            'dokument' => $dane['rodzaj'] === ZgloszenieKierownika::RODZAJ_DOKUMENT ? $dane['dokument'] : null,
            'od' => $dane['od'] ?? null,
            'do' => $dane['do'] ?? null,
            'uwaga' => $dane['uwaga'] ?? null,
            'status' => ZgloszenieKierownika::STATUS_NOWE,
        ])->save();

        if (Request::hasFile('plik')) {
            $plik = Request::file('plik');
            $nazwa = Str::random(40).'.'.strtolower($plik->getClientOriginalExtension() ?: 'bin');
            $plik->storeAs(self::KATALOG.'/'.$zgloszenie->id, $nazwa);
            $zgloszenie->forceFill([
                'plik_sciezka' => self::KATALOG.'/'.$zgloszenie->id.'/'.$nazwa,
                'plik_nazwa' => $plik->getClientOriginalName(),
            ])->save();
        }

        $this->powiadomKadry($zgloszenie);

        return Redirect::back()->with('success', 'Zgłoszenie poszło do kadr.');
    }

    /** Kadry: obsłużone (po zrobieniu zmiany) albo odrzucone, z odpowiedzią dla kierownika. */
    public function obsluz(ZgloszenieKierownika $zgloszenie): RedirectResponse
    {
        $dane = Request::validate([
            'status' => ['required', Rule::in([ZgloszenieKierownika::STATUS_OBSLUZONE, ZgloszenieKierownika::STATUS_ODRZUCONE])],
            'odpowiedz' => ['nullable', 'string', 'max:2000'],
        ]);

        $zgloszenie->forceFill([
            'status' => $dane['status'],
            'odpowiedz' => $dane['odpowiedz'] ?? null,
            'obsluzyl_id' => Auth::id(),
            'obsluzone_at' => now(),
        ])->save();

        return Redirect::back()->with('success', $dane['status'] === ZgloszenieKierownika::STATUS_OBSLUZONE
            ? 'Zgłoszenie obsłużone.'
            : 'Zgłoszenie odrzucone.');
    }

    /** Skan widzi ten, kto obsługuje zgłoszenia, i ten, kto je wysłał. */
    public function plik(ZgloszenieKierownika $zgloszenie): BinaryFileResponse
    {
        $user = Auth::user();
        abort_unless(
            $user->moze(Uprawnienie::ZMIANY_KADROWE) || (int) $zgloszenie->user_id === (int) $user->id,
            403,
        );

        abort_if(! $zgloszenie->plik_sciezka || ! Storage::exists($zgloszenie->plik_sciezka), 404);

        // Zdjęcie i PDF przeglądarka pokaże sama.
        return response()->file(Storage::path($zgloszenie->plik_sciezka));
    }

    /**
     * Wiersz zgłoszenia do widoków: zakładka Pracownicy (status dla
     * kierownika) i ekran Kadry (pełna obsługa).
     *
     * @return array<string, mixed>
     */
    /**
     * Zatwierdzenie zgłoszenia z automatycznym naniesieniem zmiany:
     * urlop → wstawia nieobecność (KCP maluje się sam), zjazd → skraca pobyt,
     * przeniesienie → skraca stary pobyt i zakłada nowy na budowie docelowej.
     * Rodzaje bez jednoznacznej zmiany (dokument, inne) tu nie wchodzą —
     * te kadry obsługują ręcznie i zamykają zwykłym „Obsłużone".
     */
    public function zatwierdz(ZgloszenieKierownika $zgloszenie): RedirectResponse
    {
        abort_unless($zgloszenie->status === ZgloszenieKierownika::STATUS_NOWE, 422, 'To zgłoszenie jest już obsłużone.');

        $dane = Request::validate([
            'kod' => ['nullable', Rule::in(['UW', 'UO', 'UB', 'UŻ'])],
            'organization_docelowa_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'odpowiedz' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($zgloszenie, $dane) {
                match ($zgloszenie->rodzaj) {
                    ZgloszenieKierownika::RODZAJ_URLOP => $this->zastosujUrlop($zgloszenie, $dane['kod'] ?? null),
                    ZgloszenieKierownika::RODZAJ_ZJAZD => $this->zastosujZjazd($zgloszenie),
                    ZgloszenieKierownika::RODZAJ_PRZENIESIENIE => $this->zastosujPrzeniesienie($zgloszenie, $dane['organization_docelowa_id'] ?? null),
                    default => throw new \RuntimeException('Tego zgłoszenia nie naniosę automatycznie — obsłuż je ręcznie i kliknij „Obsłużone".'),
                };

                $zgloszenie->forceFill([
                    'status' => ZgloszenieKierownika::STATUS_OBSLUZONE,
                    'odpowiedz' => $dane['odpowiedz'] ?? null,
                    'obsluzyl_id' => Auth::id(),
                    'obsluzone_at' => now(),
                ])->save();
            });
        } catch (\RuntimeException $e) {
            return Redirect::back()->withErrors(['zatwierdz' => $e->getMessage()]);
        }

        return Redirect::back()->with('success', 'Zatwierdzone — zmiana naniesiona, KCP zaktualizowane.');
    }

    private function zastosujUrlop(ZgloszenieKierownika $z, ?string $kod): void
    {
        if (! $z->od || ! $z->do) {
            throw new \RuntimeException('Urlop bez dat — obsłuż ręcznie.');
        }

        // Z wniosku znamy dokładny kod; przy zgłoszeniu ręcznym domyślnie UW.
        $kod = $z->wniosek?->rodzaj ?: ($kod ?: 'UW');
        $status = ShiftStatus::where('code', $kod)->first();

        if (! $status) {
            throw new \RuntimeException('Brak kodu nieobecności „'.$kod.'” w słowniku.');
        }

        Holiday::create([
            'contact_id' => $z->contact_id,
            'shift_status_id' => $status->id,
            'start' => $z->od->format('Y-m-d'),
            'end' => $z->do->format('Y-m-d'),
        ]);
    }

    private function zastosujZjazd(ZgloszenieKierownika $z): void
    {
        $data = $z->do ?: $z->od;

        if (! $data) {
            throw new \RuntimeException('Zjazd bez daty — obsłuż ręcznie.');
        }

        $pobyt = $this->pobytDoSkrocenia($z->contact_id, $z->organization_id, $data->format('Y-m-d'));

        if (! $pobyt) {
            throw new \RuntimeException('Nie znalazłem pobytu na tej budowie do skrócenia — obsłuż ręcznie.');
        }

        $pobyt->update(['end' => $data->format('Y-m-d')]);
    }

    private function zastosujPrzeniesienie(ZgloszenieKierownika $z, ?int $celId): void
    {
        if (! $celId) {
            throw new \RuntimeException('Wskaż budowę docelową.');
        }

        if (! $z->od) {
            throw new \RuntimeException('Brak daty przeniesienia — obsłuż ręcznie.');
        }

        $start = $z->od->format('Y-m-d');
        $pobyt = $this->pobytDoSkrocenia($z->contact_id, $z->organization_id, $start);

        // Stary pobyt kończy się dzień przed wejściem na nową budowę.
        if ($pobyt) {
            $pobyt->update(['end' => $z->od->copy()->subDay()->format('Y-m-d')]);
        }

        ContactWorkDate::create([
            'contact_id' => $z->contact_id,
            'organization_id' => $celId,
            'start' => $start,
            'end' => $z->do?->format('Y-m-d'),
        ]);
    }

    /** Pobyt tej osoby na tej budowie, obejmujący wskazany dzień (najświeższy). */
    private function pobytDoSkrocenia(int $contactId, ?int $orgId, string $data): ?ContactWorkDate
    {
        return ContactWorkDate::where('contact_id', $contactId)
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->where('start', '<=', $data)
            ->where(fn ($q) => $q->whereNull('end')->orWhere('end', '>=', $data))
            ->orderByDesc('start')
            ->first();
    }

    public static function wiersz(ZgloszenieKierownika $z, ?int $pobytId = null): array
    {
        $autor = $z->autor;
        $obsluzyl = $z->obsluzyl;

        return [
            'id' => $z->id,
            'contact_id' => $z->contact_id,
            'pracownik' => $z->contact ? trim($z->contact->last_name.' '.$z->contact->first_name) : '—',
            'organization_id' => $z->organization_id,
            'budowa' => $z->organization?->nazwaBud,
            'rodzaj' => $z->rodzaj,
            'rodzaj_label' => $z->rodzajLabel(),
            'dokument' => $z->dokument,
            'dodaj_dokument_url' => $z->adresDodaniaDokumentu(),
            'od' => $z->od?->format('Y-m-d'),
            'do' => $z->do?->format('Y-m-d'),
            'uwaga' => $z->uwaga,
            'plik' => $z->plik_sciezka ? '/zgloszenia/'.$z->id.'/plik' : null,
            'plik_nazwa' => $z->plik_nazwa,
            'status' => $z->status,
            'status_label' => $z->statusLabel(),
            'autor' => $autor ? trim($autor->first_name.' '.$autor->last_name) : '—',
            'kiedy' => $z->created_at?->format('d.m.Y H:i'),
            'obsluzyl' => $obsluzyl ? trim($obsluzyl->first_name.' '.$obsluzyl->last_name) : null,
            'obsluzone_kiedy' => $z->obsluzone_at?->format('d.m.Y H:i'),
            'odpowiedz' => $z->odpowiedz,
            // Skróty dla kadr: poprawić daty pobytu, wstawić nieobecność.
            'pobyt_id' => $pobytId,
            'ma_wniosek' => (bool) $z->wniosek_id,
        ];
    }

    private function powiadomKadry(ZgloszenieKierownika $zgloszenie): void
    {
        app(PowiadomieniaKadr::class)->oZgloszeniu($zgloszenie, Auth::id());
    }
}
