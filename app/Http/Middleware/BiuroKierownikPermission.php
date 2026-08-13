<?php

namespace App\Http\Middleware;

use App\Models\Contact;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiuroKierownikPermission
{
    /**
     * Dostęp dla admina, biura i kierownika.
     * Kierownik dostaje dostęp do konkretnej budowy (parametr trasy
     * `organization` lub `build`) tylko jeśli przejdzie OrganizationPolicy@view
     * — czyli jest AKTYWNIE w kierownictwie tej budowy. Analogicznie dla
     * pracownika (parametr `contact`) — musi być na jego budowie (ContactPolicy@view).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user->isOffice() && !$user->isKierownik()) {
            abort(403);
        }

        // Biuro/admin mają pełny dostęp — sprawdzamy zakres tylko dla kierownika.
        if ($user->isKierownik()) {
            // Dostęp do budowy (parametr organization / build)
            $orgParam = $request->route('organization') ?: $request->route('build');

            if ($orgParam !== null) {
                $organization = $orgParam instanceof Organization
                    ? $orgParam
                    : Organization::withTrashed()->find(is_object($orgParam) ? $orgParam->id : $orgParam);

                if (!$organization || $user->cannot('view', $organization)) {
                    abort(403, 'Nie masz uprawnień do tej budowy.');
                }
            }

            // Dostęp do pracownika (parametr contact)
            $contactParam = $request->route('contact');

            if ($contactParam !== null) {
                $contact = $contactParam instanceof Contact
                    ? $contactParam
                    : Contact::withTrashed()->find(is_object($contactParam) ? $contactParam->id : $contactParam);

                if (!$contact || $user->cannot('view', $contact)) {
                    abort(403, 'Nie masz uprawnień do tego pracownika.');
                }
            }
        }

        return $next($request);
    }
}
