<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactWorkDate;
use App\Models\DostepPracownika;
use App\Models\WniosekUrlopowy;
use App\Notifications\WniosekUrlopowyNotification;
use App\Services\KierownicyBudowy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Strona pracownika na telefon — bez konta w HRM. Wejście osobistym
 * linkiem (/u/{token}) i PIN-em; widać tylko własne wnioski urlopowe.
 */
class PortalPracownikaController extends Controller
{
    private const SESJA = 'portal_dostep_id';

    public function wejscie(string $token): Response
    {
        $dostep = DostepPracownika::zTokenu($token);
        if (! $dostep) {
            return Inertia::render('Portal/Brak');
        }

        if (! $this->zalogowany($dostep)) {
            return Inertia::render('Portal/Pin', [
                'token' => $token,
                'imie' => $dostep->contact?->first_name,
                'ustawianie' => ! $dostep->maPin(),
                'zablokowany' => $dostep->jestZablokowany(),
            ]);
        }

        return $this->portal($dostep, $token);
    }

    public function pin(string $token): RedirectResponse
    {
        $dostep = DostepPracownika::zTokenu($token);
        abort_if(! $dostep, 404);

        $dane = Request::validate([
            'pin' => ['required', 'digits_between:4,6'],
            'pin_confirmation' => [Rule::requiredIf(! $dostep->maPin()), 'nullable', 'same:pin'],
        ], [
            'pin.digits_between' => 'PIN to 4 do 6 cyfr.',
            'pin_confirmation.same' => 'Oba PIN-y muszą być takie same.',
        ]);

        if (! $dostep->maPin()) {
            $dostep->ustawPin($dane['pin']);
            $dostep->forceFill(['ostatnie_wejscie_at' => now()])->save();
        } elseif (! $dostep->sprawdzPin($dane['pin'])) {
            return Redirect::back()->withErrors(['pin' => $dostep->jestZablokowany()
                ? 'Za dużo prób. Spróbuj za '.DostepPracownika::BLOKADA_MINUT.' minut.'
                : 'Zły PIN.']);
        }

        Session::put(self::SESJA, $dostep->id);

        return Redirect::to('/u/'.$token);
    }

    public function wniosek(string $token): RedirectResponse
    {
        $dostep = DostepPracownika::zTokenu($token);
        abort_if(! $dostep || ! $this->zalogowany($dostep), 403);

        $dane = Request::validate([
            'rodzaj' => ['required', Rule::in(array_keys(WniosekUrlopowy::RODZAJE))],
            'od' => ['required', 'date', 'after_or_equal:today'],
            'do' => ['required', 'date', 'after_or_equal:od'],
            'uwaga' => ['nullable', 'string', 'max:500'],
        ], [
            'od.after_or_equal' => 'Urlop nie może zaczynać się w przeszłości.',
            'do.after_or_equal' => 'Data „do” nie może być przed datą „od”.',
        ]);

        $wniosek = new WniosekUrlopowy();
        $wniosek->forceFill([
            'contact_id' => $dostep->contact_id,
            'rodzaj' => $dane['rodzaj'],
            'od' => $dane['od'],
            'do' => $dane['do'],
            'uwaga' => $dane['uwaga'] ?? null,
            'status' => WniosekUrlopowy::STATUS_ZLOZONY,
        ])->save();

        $this->powiadomKierownikow($wniosek);

        return Redirect::to('/u/'.$token)->with('success', 'Wniosek wysłany do kierownika.');
    }

    public function wyloguj(string $token): RedirectResponse
    {
        Session::forget(self::SESJA);

        return Redirect::to('/u/'.$token);
    }

    private function zalogowany(DostepPracownika $dostep): bool
    {
        return (int) Session::get(self::SESJA) === (int) $dostep->id;
    }

    private function portal(DostepPracownika $dostep, string $token): Response
    {
        $contact = $dostep->contact;
        $dzis = now()->toDateString();
        $budowa = ContactWorkDate::with('organization')
            ->where('contact_id', $contact->id)
            ->activeOn($dzis)
            ->orderByDesc('start')
            ->first()?->organization?->nazwaBud;

        return Inertia::render('Portal/Wnioski', [
            'token' => $token,
            'pracownik' => ['imie' => $contact->first_name, 'nazwisko' => $contact->last_name, 'budowa' => $budowa],
            'rodzaje' => WniosekUrlopowy::RODZAJE,
            'wnioski' => WniosekUrlopowy::where('contact_id', $contact->id)
                ->orderByDesc('id')->limit(20)->get()
                ->map(fn (WniosekUrlopowy $w) => [
                    'id' => $w->id,
                    'rodzaj' => $w->rodzajLabel(),
                    'od' => $w->od->format('Y-m-d'),
                    'do' => $w->do->format('Y-m-d'),
                    'dni' => $w->dni(),
                    'status' => $w->status,
                    'status_label' => $w->statusLabel(),
                    'odpowiedz' => $w->odpowiedz,
                    'zlozony' => $w->created_at?->format('d.m.Y'),
                ]),
        ]);
    }

    /** Dzwonek do kierownictwa budów, na których pracownik dziś jest. */
    private function powiadomKierownikow(WniosekUrlopowy $wniosek): void
    {
        try {
            $orgIds = ContactWorkDate::where('contact_id', $wniosek->contact_id)
                ->activeOn(now()->toDateString())
                ->pluck('organization_id')->unique()->map(fn ($id) => (int) $id)->all();

            $kierownicy = app(KierownicyBudowy::class)->uzytkownicy($orgIds);
            if ($kierownicy->isNotEmpty()) {
                Notification::send($kierownicy, new WniosekUrlopowyNotification($wniosek));
            }
        } catch (\Throwable $e) {
            Log::warning('Nie udało się powiadomić kierownika o wniosku urlopowym: '.$e->getMessage(), ['wniosek_id' => $wniosek->id]);
        }
    }
}
