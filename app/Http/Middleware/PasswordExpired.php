<?php

namespace App\Http\Middleware;

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
//        $user = User::select('password_changed_at')->where('email', $request->email)->first();

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
