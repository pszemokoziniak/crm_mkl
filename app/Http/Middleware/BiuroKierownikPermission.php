<?php

namespace App\Http\Middleware;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiuroKierownikPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!in_array($user->owner, [1, 2, 3])) {
            abort(403);
        }

        // Jeśli to kierownik, sprawdź czy ma dostęp do konkretnej budowy (jeśli ID budowy jest w URL)
        if ($user->owner === 3) {
            $organization = $request->route('organization') ?: $request->route('build');

            if ($organization) {
                // Pobierz ID organizacji (obsługa zarówno obiektu modelu jak i ID)
                $orgId = is_object($organization) ? $organization->id : $organization;

                $contact = Contact::where('user_id', $user->id)
                    ->orWhere(function($query) use ($user) {
                        $query->where('first_name', $user->first_name)
                              ->where('last_name', $user->last_name);
                    })
                    ->first();

                $contact_id = $contact ? $contact->id : null;

                if (!$contact_id) {
                    abort(403, 'Użytkownik nie jest powiązany z żadnym pracownikiem.');
                }

                // Sprawdź przypisanie w tabeli contact_work_dates (bez ograniczenia do dzisiejszej daty)
                $isAssigned = ContactWorkDate::where('organization_id', $orgId)
                    ->where('contact_id', $contact_id)
                    ->exists();

                // Dodatkowy check dla pól w tabeli organizations (stara metoda przypisania)
                $isMainKierownik = Organization::where('id', $orgId)
                    ->where(function($q) use ($contact_id) {
                        $q->where('kierownikBud_id', $contact_id)
                          ->orWhere('inzynier_id', $contact_id);
                    })->exists();

                if (!$isAssigned && !$isMainKierownik) {
                    abort(403, 'Nie masz uprawnień do tej budowy.');
                }
            }
        }

        return $next($request);
    }
}
