<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactWorkDate;
use App\Models\WniosekUrlopowy;
use App\Models\ZgloszenieKierownika;
use App\Services\PowiadomieniaKadr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

/**
 * Kierownik budowy / projektu zatwierdza albo odrzuca wniosek urlopowy
 * złożony z telefonu. Zatwierdzony idzie do kadr jako zgłoszenie urlopu —
 * tą samą drogą, co zgłoszenia od kierowników.
 */
class WnioskiUrlopoweController extends Controller
{
    public function rozpatrz(WniosekUrlopowy $wniosek): RedirectResponse
    {
        $user = Auth::user();
        $contact = $wniosek->contact;
        abort_unless($contact && $user->can('view', $contact), 403, 'To nie jest pracownik z Twojej budowy.');
        abort_unless($wniosek->status === WniosekUrlopowy::STATUS_ZLOZONY, 422, 'Ten wniosek jest już rozpatrzony.');

        $dane = Request::validate([
            'status' => ['required', Rule::in([WniosekUrlopowy::STATUS_ZATWIERDZONY, WniosekUrlopowy::STATUS_ODRZUCONY])],
            'odpowiedz' => ['nullable', 'string', 'max:500'],
        ]);

        $wniosek->forceFill([
            'status' => $dane['status'],
            'odpowiedz' => $dane['odpowiedz'] ?? null,
            'rozpatrzyl_id' => $user->id,
            'rozpatrzony_at' => now(),
        ])->save();

        if ($dane['status'] === WniosekUrlopowy::STATUS_ZATWIERDZONY) {
            $this->przekazKadrom($wniosek);
        }

        return Redirect::back()->with('success', $dane['status'] === WniosekUrlopowy::STATUS_ZATWIERDZONY
            ? 'Wniosek zatwierdzony — poszedł do kadr.'
            : 'Wniosek odrzucony.');
    }

    /** Zatwierdzony wniosek = zgłoszenie urlopu do kadr, liczone jako wniosek (bez skanu). */
    private function przekazKadrom(WniosekUrlopowy $wniosek): void
    {
        $user = Auth::user();
        $orgId = ContactWorkDate::where('contact_id', $wniosek->contact_id)
            ->activeOn(now()->toDateString())
            ->orderByDesc('start')
            ->value('organization_id')
            ?? ContactWorkDate::where('contact_id', $wniosek->contact_id)->orderByDesc('start')->value('organization_id');

        $zgloszenie = new ZgloszenieKierownika();
        $zgloszenie->forceFill([
            'contact_id' => $wniosek->contact_id,
            'organization_id' => (int) $orgId,
            'user_id' => $user->id,
            'rodzaj' => ZgloszenieKierownika::RODZAJ_URLOP,
            'od' => $wniosek->od->format('Y-m-d'),
            'do' => $wniosek->do->format('Y-m-d'),
            'uwaga' => trim('Wniosek z telefonu ('.$wniosek->rodzajLabel().'), zatwierdził(a) '.trim($user->first_name.' '.$user->last_name).'.'
                .($wniosek->uwaga ? ' Uwaga pracownika: '.$wniosek->uwaga : '')),
            'wniosek_id' => $wniosek->id,
            'status' => ZgloszenieKierownika::STATUS_NOWE,
        ])->save();

        $wniosek->forceFill(['zgloszenie_id' => $zgloszenie->id])->save();

        app(PowiadomieniaKadr::class)->oZgloszeniu($zgloszenie, $user->id);
    }

    /**
     * Wiersz do pulpitu kierownika.
     *
     * @return array<string, mixed>
     */
    public static function wiersz(WniosekUrlopowy $w): array
    {
        $c = $w->contact;

        return [
            'id' => $w->id,
            'contact_id' => $w->contact_id,
            'pracownik' => $c ? trim($c->last_name.' '.$c->first_name) : '—',
            'rodzaj' => $w->rodzajLabel(),
            'od' => $w->od->format('Y-m-d'),
            'do' => $w->do->format('Y-m-d'),
            'dni' => $w->dni(),
            'uwaga' => $w->uwaga,
            'zlozony' => $w->created_at?->format('d.m.Y H:i'),
        ];
    }
}
