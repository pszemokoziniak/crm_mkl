<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelfOrBiuroPermission
{
    /**
     * Dostęp do profilu użytkownika (users/{user}).
     * Admin/biuro — do każdego. Pozostali (np. kierownik) — tylko do
     * WŁASNEGO profilu (route `user` === zalogowany). Zmianę roli/konta
     * i tak blokuje UsersController@update (owner/contact tylko dla biura).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if ($user->isOffice()) {
            return $next($request);
        }

        $routeUser = $request->route('user');
        $routeUserId = $routeUser instanceof User
            ? $routeUser->id
            : (is_object($routeUser) ? ($routeUser->id ?? null) : $routeUser);

        if ($routeUserId !== null && (int) $routeUserId === (int) $user->id) {
            return $next($request);
        }

        abort(403, 'Masz dostęp tylko do własnego profilu.');
    }
}
