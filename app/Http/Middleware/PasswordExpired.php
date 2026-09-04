<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PodszywanieController;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Hamcrest\Core\IsNull;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasswordExpired
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
        // Administrator wszedł na cudze konto tylko po to, żeby zobaczyć
        // co ta osoba widzi — nie zmienia jej hasła i nie może przez to
        // utknąć na ekranie wymuszonej zmiany.
        if ($request->session()->has(PodszywanieController::KLUCZ_SESJI)) {
            return $next($request);
        }

        if (Auth::user()->password_changed_at === null) {
            return redirect()->route('password.expired');
        } else {
            if (Carbon::now()->diffInDays(Auth::user()->password_changed_at) >= config('auth.password_expires_days')) {
                return redirect()->route('password.expired');
            }
        }

        return $next($request);
    }
}
