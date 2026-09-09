<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Contact;
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
        ];
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
