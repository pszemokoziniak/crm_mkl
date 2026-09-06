<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Contact;
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

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
