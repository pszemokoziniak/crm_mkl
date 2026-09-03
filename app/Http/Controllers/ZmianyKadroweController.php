<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ZmianaKadrowa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Skrzynka kadr: zmiany pobytów na budowach do przygotowania aneksów.
 */
class ZmianyKadroweController extends Controller
{
    public function index(): Response
    {
        $pokaz = Request::query('pokaz', 'nieobsluzone');

        $zmiany = ZmianaKadrowa::with(['contact', 'budowaZ', 'budowaDo', 'autor', 'obsluzylUser'])
            ->when($pokaz !== 'wszystkie', fn ($query) => $query->nieobsluzone())
            ->orderByDesc('created_at')
            ->limit(300)
            ->get()
            ->map(fn (ZmianaKadrowa $z) => $this->wiersz($z));

        // Grupujemy po paczce — przeniesienie ekipy to dla kadr jedna sprawa.
        $paczki = $zmiany
            ->groupBy('paczka')
            ->map(fn ($grupa) => [
                'paczka' => $grupa->first()['paczka'],
                'zmiany' => $grupa->values(),
                'osob' => $grupa->pluck('contact_id')->unique()->count(),
                'autor' => $grupa->first()['autor'],
                'kiedy' => $grupa->first()['kiedy'],
                'nieobsluzonych' => $grupa->where('status', '!=', ZmianaKadrowa::STATUS_GOTOWA)->count(),
                'naglowek' => $this->naglowekPaczki($grupa),
            ])
            ->values();

        return Inertia::render('ZmianyKadrowe/Index', [
            'paczki' => $paczki,
            'filters' => ['pokaz' => $pokaz],
            'licznik' => ZmianaKadrowa::nieobsluzone()->count(),
        ]);
    }

    /** Zmiana statusu — pojedynczy wpis albo cała paczka. */
    public function update(): RedirectResponse
    {
        $dane = Request::validate([
            'status' => ['required', Rule::in([
                ZmianaKadrowa::STATUS_NOWA,
                ZmianaKadrowa::STATUS_W_PRZYGOTOWANIU,
                ZmianaKadrowa::STATUS_GOTOWA,
            ])],
            'id' => ['required_without:paczka', 'integer'],
            'paczka' => ['required_without:id', 'string'],
            'uwagi' => ['nullable', 'string', 'max:2000'],
        ]);

        $query = isset($dane['paczka']) && $dane['paczka']
            ? ZmianaKadrowa::where('paczka', $dane['paczka'])
            : ZmianaKadrowa::where('id', $dane['id']);

        $gotowa = $dane['status'] === ZmianaKadrowa::STATUS_GOTOWA;

        $query->get()->each(function (ZmianaKadrowa $zmiana) use ($dane, $gotowa) {
            $zmiana->update([
                'status' => $dane['status'],
                'handled_by' => $gotowa ? Auth::id() : null,
                'handled_at' => $gotowa ? now() : null,
                'uwagi' => $dane['uwagi'] ?? $zmiana->uwagi,
            ]);
        });

        return Redirect::back()->with('success', 'Zapisano.');
    }

    /**
     * @return array<string, mixed>
     */
    private function wiersz(ZmianaKadrowa $z): array
    {
        return [
            'id' => $z->id,
            'paczka' => $z->paczka,
            'pracownik' => $z->contact
                ? trim($z->contact->last_name.' '.$z->contact->first_name)
                : 'pracownik usunięty',
            'contact_id' => $z->contact_id,
            'typ' => $z->typ,
            'typ_label' => $z->typLabel(),
            'budowa_z' => optional($z->budowaZ)->nazwaBud,
            'budowa_do' => optional($z->budowaDo)->nazwaBud,
            'stary_termin' => $z->old_start ? $z->old_start->format('Y-m-d').' → '.optional($z->old_end)->format('Y-m-d') : null,
            'nowy_termin' => $z->new_start ? $z->new_start->format('Y-m-d').' → '.optional($z->new_end)->format('Y-m-d') : null,
            'status' => $z->status,
            'status_label' => $z->statusLabel(),
            'autor' => $z->autor ? trim($z->autor->first_name.' '.$z->autor->last_name) : '—',
            'kiedy' => $z->created_at?->format('d.m.Y H:i'),
            'obsluzyl' => $z->obsluzylUser ? trim($z->obsluzylUser->first_name.' '.$z->obsluzylUser->last_name) : null,
            'obsluzono' => $z->handled_at?->format('d.m.Y H:i'),
            'uwagi' => $z->uwagi,
            'link_aneks' => $this->linkDoAneksu($z),
        ];
    }

    /**
     * Adres formularza aneksu z wypełnionymi danymi tej zmiany — kadry nie
     * muszą przepisywać budowy ani terminu.
     */
    private function linkDoAneksu(ZmianaKadrowa $z): ?string
    {
        if (! $z->contact_id) {
            return null;
        }

        $budowa = optional($z->budowaDo)->nazwaBud ?: optional($z->budowaZ)->nazwaBud;
        $od = $z->new_start ?: $z->old_start;
        $do = $z->new_end ?: $z->old_end;

        $parametry = array_filter([
            'rodzaj' => 'aneks',
            'budowa' => $budowa,
            'od' => optional($od)->format('Y-m-d'),
            'do' => optional($do)->format('Y-m-d'),
            'uwagi' => $this->opisZmiany($z),
        ]);

        return '/contacts/'.$z->contact_id.'/umowa?'.http_build_query($parametry);
    }

    /** Jedno zdanie do pola "uwagi" w dokumencie. */
    private function opisZmiany(ZmianaKadrowa $z): string
    {
        $skad = optional($z->budowaZ)->nazwaBud;
        $dokad = optional($z->budowaDo)->nazwaBud;

        if ($z->typ === ZmianaKadrowa::TYP_PRZENIESIENIE && $skad && $dokad) {
            return 'Przeniesienie z budowy '.$skad.' na budowę '.$dokad.'.';
        }

        return $z->typLabel().($dokad ? ' — '.$dokad : ($skad ? ' — '.$skad : ''));
    }

    /** Jedno zdanie opisujące całą paczkę, np. "Przeniesienie 6 osób: A → B". */
    private function naglowekPaczki($grupa): string
    {
        $osob = $grupa->pluck('contact_id')->unique()->count();
        $skad = $grupa->pluck('budowa_z')->filter()->unique()->values();
        $dokad = $grupa->pluck('budowa_do')->filter()->unique()->values();
        $typy = $grupa->pluck('typ_label')->unique();

        $opis = $typy->count() === 1 ? $typy->first() : 'Zmiany pobytów';
        $osobyOpis = $osob === 1 ? $grupa->first()['pracownik'] : $osob.' osób';

        $kierunek = '';

        if ($skad->count() === 1 && $dokad->count() === 1 && $skad->first() !== $dokad->first()) {
            $kierunek = ': '.$skad->first().' → '.$dokad->first();
        } elseif ($dokad->count() === 1) {
            $kierunek = ': '.$dokad->first();
        } elseif ($skad->count() === 1) {
            $kierunek = ': '.$skad->first();
        }

        return $opis.' — '.$osobyOpis.$kierunek;
    }
}
