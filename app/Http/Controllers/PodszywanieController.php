<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

/**
 * Wejście administratora na konto innego użytkownika — do sprawdzania,
 * co dana osoba widzi. Id administratora zostaje w sesji, więc powrót
 * do siebie jest jednym kliknięciem i nie wymaga ponownego logowania.
 */
class PodszywanieController extends Controller
{
    public const KLUCZ_SESJI = 'podszywanie_admin_id';

    public function wejdz(Request $request, User $user)
    {
        // Wejście z konta, na które ktoś już wszedł, zaplątałoby powrót.
        if ($request->session()->has(self::KLUCZ_SESJI)) {
            return Redirect::back()->with('error', 'Najpierw wróć na swoje konto.');
        }

        $admin = Auth::user();

        if ($admin->id === $user->id) {
            return Redirect::back()->with('error', 'Jesteś już na swoim koncie.');
        }

        if ($user->deleted_at) {
            return Redirect::back()->with('error', 'Konto jest w archiwum.');
        }

        if (! $user->active) {
            return Redirect::back()->with('error', 'Konto jest zablokowane.');
        }

        Log::info('Podszywanie: wejście na cudze konto', [
            'admin' => $admin->email,
            'konto' => $user->email,
        ]);

        $request->session()->regenerate();
        $request->session()->put(self::KLUCZ_SESJI, $admin->id);

        Auth::login($user);

        return Redirect::route('dashboard')
            ->with('success', 'Pracujesz teraz jako '.$user->name.'.');
    }

    public function wroc(Request $request)
    {
        $adminId = $request->session()->get(self::KLUCZ_SESJI);

        if (! $adminId) {
            return Redirect::route('dashboard');
        }

        $admin = User::find($adminId);

        // Konto administratora zniknęło albo zostało zablokowane w międzyczasie —
        // nie ma dokąd wracać, więc wylogowujemy zamiast zostawiać w cudzej sesji.
        if (! $admin || ! $admin->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::route('login')->with('error', 'Zaloguj się ponownie.');
        }

        Log::info('Podszywanie: powrót na swoje konto', [
            'admin' => $admin->email,
            'konto' => optional(Auth::user())->email,
        ]);

        $request->session()->forget(self::KLUCZ_SESJI);
        $request->session()->regenerate();

        Auth::login($admin);

        return Redirect::route('users')->with('success', 'Wróciłeś na swoje konto.');
    }
}
