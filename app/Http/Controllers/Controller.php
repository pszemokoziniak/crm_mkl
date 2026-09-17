<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;
use App\Models\ContactWorkDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Dane pracownika do nagłówka podstrony — żeby na każdej było widać,
     * czyją kartę się ogląda.
     *
     * @return array{id: int, nazwa: string}|null
     */
    protected function danePracownika(?Contact $contact): ?array
    {
        if (! $contact) {
            return null;
        }

        return [
            'id' => $contact->id,
            'nazwa' => trim($contact->last_name.' '.$contact->first_name),
            // Kierownik zgłasza kadrom braki w dokumentach z każdej podstrony
            // pracownika — potrzebuje do tego budowy, na której go prowadzi.
            'zgloszenie' => $this->budowaDoZgloszenia($contact),
        ];
    }

    /**
     * Budowa, z której zalogowany kierownik może zgłosić sprawę tego
     * pracownika: jego aktywna budowa, na której pracownik ma pobyt
     * (dzisiejszy, a gdy takiego nie ma — dowolny). Biuro nie zgłasza.
     *
     * @return array{organization_id: int, budowa: string}|null
     */
    private function budowaDoZgloszenia(Contact $contact): ?array
    {
        $user = Auth::user();
        if (! $user || ! $user->prowadziBudowy()) {
            return null;
        }

        $moje = Organization::mojeAktywneBudowy($user)->pluck('nazwaBud', 'id');
        if ($moje->isEmpty()) {
            return null;
        }

        $dzis = now()->toDateString();
        $pobyty = ContactWorkDate::where('contact_id', $contact->id)
            ->whereIn('organization_id', $moje->keys())
            ->orderByDesc('start')
            ->get(['organization_id', 'start', 'end']);

        $pobyt = $pobyty->first(fn ($p) => (string) $p->start <= $dzis && ($p->end === null || (string) $p->end >= $dzis))
            ?? $pobyty->first();

        return $pobyt ? ['organization_id' => (int) $pobyt->organization_id, 'budowa' => $moje[$pobyt->organization_id]] : null;
    }

    /**
     * Wpis z podstrony musi należeć do pracownika z adresu.
     *
     * Trasy niosą i pracownika, i wpis, ale nic ich dotąd nie wiązało:
     * /contacts/45/bhp/999 otwierało wpis obcego pracownika i zapisywało go
     * spod cudzej karty.
     */
    protected function wpisPracownika(Contact $contact, Model $wpis): void
    {
        abort_unless((int) $wpis->contact_id === (int) $contact->id, 404);
    }

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
