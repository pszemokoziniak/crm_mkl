<?php

namespace App\Http\Middleware;

use App\Enums\Uprawnienie;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * `wlasny-profil-lub:uzytkownicy.edycja` — własne konto edytuje każdy,
 * cudze tylko ten, kto ma podane uprawnienie.
 */
class WlasnyProfilLub
{
    public function handle(Request $request, Closure $next, string $uprawnienie)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if ($user->moze(Uprawnienie::from($uprawnienie))) {
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
