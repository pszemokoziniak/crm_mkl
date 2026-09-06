<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\PasswordExpiredRequest;
use App\Models\Logowanie;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
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
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            Logowanie::zapisz($request, null, false, Logowanie::POWOD_BRAK_KONTA);

            return Redirect::route('login')->with('error', 'Nie ma takiego użytkownika.');
        }

        if (! $user->active) {
            Logowanie::zapisz($request, $user, false, Logowanie::POWOD_ZABLOKOWANE);

            return Redirect::route('login')->with('error', 'Konto zablokowane.');
        }

        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            // Błędne hasło też jest zdarzeniem, o którym warto wiedzieć.
            Logowanie::zapisz($request, $user, false, Logowanie::POWOD_ZLE_HASLO);

            throw $e;
        }

        $request->session()->regenerate();

        $user->login_time = Carbon::now('Europe/Warsaw');
        $user->save();

        Logowanie::zapisz($request, $user, true);

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
