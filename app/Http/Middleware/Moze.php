<?php

namespace App\Http\Middleware;

use App\Enums\Uprawnienie;
use App\Models\Contact;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * `moze:kartoteki.edycja` — wpuszcza, gdy rola ma dane uprawnienie
 * (kilka po przecinku = wszystkie naraz). Potem, dla ról prowadzących
 * wybrane budowy, sprawdza zakres: budowa i pracownik z adresu muszą być
 * "ich". Biuro i admin zakresu nie mają — widzą wszystko.
 */
class Moze
{
    public function handle(Request $request, Closure $next, string ...$uprawnienia)
    {
        $user = Auth::user();

        foreach ($uprawnienia as $nazwa) {
            if (! $user->moze(Uprawnienie::from($nazwa))) {
                abort(403);
            }
        }

        if ($user->prowadziBudowy()) {
            $this->sprawdzZakres($request);
        }

        return $next($request);
    }

    private function sprawdzZakres(Request $request): void
    {
        $user = Auth::user();

        $orgParam = $request->route('organization') ?: $request->route('build');

        if ($orgParam !== null) {
            $organization = $orgParam instanceof Organization
                ? $orgParam
                : Organization::withTrashed()->find(is_object($orgParam) ? $orgParam->id : $orgParam);

            if (! $organization || $user->cannot('view', $organization)) {
                abort(403, 'Nie masz uprawnień do tej budowy.');
            }
        }

        // Część tras nazywa ten parametr `contact`, część `contact_id` —
        // bez obu nazw zawężenie po cichu nie zadziała i kierownik
        // zobaczyłby cudzego pracownika.
        $contactParam = $request->route('contact') ?: $request->route('contact_id');

        if ($contactParam !== null) {
            $contact = $contactParam instanceof Contact
                ? $contactParam
                : Contact::withTrashed()->find(is_object($contactParam) ? $contactParam->id : $contactParam);

            if (! $contact || $user->cannot('view', $contact)) {
                abort(403, 'Nie masz uprawnień do tego pracownika.');
            }
        }
    }
}
