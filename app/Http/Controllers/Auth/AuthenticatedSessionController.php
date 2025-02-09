<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\PasswordExpiredRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $checkActiveStatus = User::where('email', $request->email)->get()->map->only('active')->pluck('active');
        if ( !isset($checkActiveStatus[0]) )
        {
            return Redirect::route('login')->with('error', 'Nie ma takiego użytkownika.');
        }
        if ($checkActiveStatus[0]===0)
        {
            return Redirect::route('login')->with('error', 'Konto zablokowane.');
        }
        $request->authenticate();
        $request->session()->regenerate();

        $login_time = User::where('email', $request->email)->first();
        $login_time->login_time = Carbon::now('Europe/Warsaw');
        $login_time->save();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function expired()
    {
        return Inertia::render('Auth/PasswordExpired');
    }

    public function postExpired(PasswordExpiredRequest $request) {
        $request->user()->update([
            'password' => bcrypt($request->password),
            'password_changed_at' => Carbon::now()->toDateTimeString()
        ]);
        return redirect('/')->with(['success' => 'Hasło zmienione']);
    }
}
