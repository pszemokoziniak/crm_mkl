<?php

namespace App\Http\Controllers;

use App\Mail\CreateUserPassword;
use App\Models\Contact;
use App\Models\Uprawnienia;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UsersController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/Index', [
            'filters' => Request::all('search', 'role', 'trashed'),
            'users' => Auth::user()->account->users()
                ->orderByName()
                ->filter(Request::only('search', 'role', 'trashed'))
                ->get()
                ->transform(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'owner' => $user->owner,
                    'contact_id' => $user->user_id,
                    'photo' => $user->photo_path ? URL::route('image', ['path' => $user->photo_path, 'w' => 40, 'h' => 40, 'fit' => 'crop']) : null,
                    'deleted_at' => $user->deleted_at,
                    'login_time' => $user->login_time,
                ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create');
    }

    /**
     * Konta zakładamy tylko na firmowych adresach — logowanie do HRM
     * ma być powiązane z pocztą firmową, nie z prywatną skrzynką.
     */
    private const DOMENA_FIRMOWA = '@mkl.pl';

    /**
     * Adres na małe litery, żeby "Jan.Kowalski@MKL.PL" nie zakładał
     * drugiego konta obok "jan.kowalski@mkl.pl" i przechodził sprawdzenie domeny.
     */
    private function znormalizujEmail(): void
    {
        $email = Request::get('email');

        if (is_string($email)) {
            Request::merge(['email' => mb_strtolower(trim($email))]);
        }
    }

    public function store()
    {
        $this->znormalizujEmail();

        Request::validate([
            'first_name' => ['required', 'max:50'],
            'last_name' => ['required', 'max:50'],
            'email' => ['required', 'max:50', 'email', 'ends_with:'.self::DOMENA_FIRMOWA, Rule::unique('users')],
            'owner' => ['required', 'max:10'],
            'contact_id' => ['nullable'],
            'photo' => ['nullable', 'image'],
        ],
            [
                'required'  => 'Pole jest wymagane.',
                'unique' => 'Nazwa użyta',
                'numeric' => 'Pole attribute może zawierać tylko cyfry',
                'email.ends_with' => 'Adres musi być w domenie '.self::DOMENA_FIRMOWA,
            ]
        );

        // Rotacja: hasło startowe losujemy per konto (zamiast wpisywanego ręcznie
        // przez admina). Użytkownik i tak musi je zmienić przy pierwszym logowaniu
        // (PasswordExpired: password_changed_at zostaje NULL).
        $password = $this->generateInitialPassword();

        Auth::user()->account->users()->create([
            'first_name' => Request::get('first_name'),
            'last_name' => Request::get('last_name'),
            'email' => Request::get('email'),
            'password' => $password,
            'owner' => Request::get('owner'),
            'contact_id' => Request::get('user_id'),
            'photo_path' => Request::file('photo') ? Request::file('photo')->store('users') : null,
        ]);

        Mail::send(new CreateUserPassword(Request::get('email'), $password));

        return Redirect::route('users')->with('success', 'Użytkownik utworzony.');
    }

    /**
     * Losowe, mocne hasło startowe. Bez znaków dwuznacznych (0/O, 1/l/I),
     * z gwarantowaną dużą i małą literą, cyfrą oraz znakiem specjalnym.
     */
    private function generateInitialPassword(int $length = 12): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $digits = '23456789';
        $special = '!$#%';
        $all = $upper.$lower.$digits.$special;

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $special[random_int(0, strlen($special) - 1)],
        ];
        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Wymieszanie pozycji (Fisher–Yates na random_int), żeby wymagane klasy
        // znaków nie stały zawsze na początku.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user_owner' => Auth::user()->owner,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'owner' => $user->owner,
                'contact_id' => Contact::where('user_id', $user->id)->value('id') ?? null,
                'photo' => $user->photo_path ? URL::route('image', ['path' => $user->photo_path, 'w' => 60, 'h' => 60, 'fit' => 'crop']) : null,
                'deleted_at' => $user->deleted_at,
                'active' => $user->active,
                'powiadomienia_kadrowe' => (bool) $user->powiadomienia_kadrowe,
            ],
            'contacts' => Contact::query()
                ->whereIn('funkcja_id', [1,6])
                ->where('user_id', null)
                ->get()->map->only('id', 'first_name', 'last_name', 'user_id'),

            'contact' => Contact::where('user_id', $user->id)->get()->map->only('id', 'first_name', 'last_name', 'user_id')->first(),
        ]);
    }

    public function update(User $user, Request $request)
    {
        if (App::environment('demo') && $user->isDemoUser()) {
            return Redirect::back()->with('error', 'Updating the Super Admin user is not allowed.');
        }

        $this->znormalizujEmail();

        Request::validate([
            'first_name' => ['required', 'max:50'],
            'last_name' => ['required', 'max:50'],
            'email' => ['required', 'max:50', 'email', 'ends_with:'.self::DOMENA_FIRMOWA, Rule::unique('users')->ignore($user->id)],
            'password' => [
                'nullable',
                'min:8',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
            ],
//            'owner' => ['nullable'],
            'contact_id' => ['nullable'],
            'photo' => ['nullable', 'image'],
            'powiadomienia_kadrowe' => ['boolean'],
        ],
        [
            'required'  => 'Pole jest wymagane.',
            'unique' => 'Nazwa użyta',
            'numeric' => 'Pole :attribute może zawierać tylko cyfry',
            'password.regex' => 'Hasło musi zawierać dużą literę, znak specjalny, cyfrę',
            'password.min' => 'Hasło musi zawierać 8 znaków',
            'email.ends_with' => 'Adres musi być w domenie '.self::DOMENA_FIRMOWA,
        ]
        );

        // Zmiana roli (owner) tylko dla biura/admina — kierownik edytujący własny
        // profil nie może podnieść sobie uprawnień.
        $isOffice = Auth::user()->isOffice();
        $fields = ['first_name', 'last_name', 'email'];
        if ($isOffice) {
            $fields[] = 'owner';

            // Kto dostaje e-maile o zmianach kadrowych — ustawia biuro/admin,
            // żeby dało się kogoś dopisać bez zmiany w kodzie.
            $user->update(['powiadomienia_kadrowe' => Request::boolean('powiadomienia_kadrowe')]);
        }
        $user->update(Request::only($fields));

        if (Request::file('photo')) {
            $user->update(['photo_path' => Request::file('photo')->store('users')]);
        }

        if (Request::get('password')) {
            $user->update(['password' => Request::get('password')]);
        }

        // Powiązanie User<->Pracownik również tylko dla biura.
        if ($isOffice && Request::get('contact_id') !== null) {
            $contact = Contact::find((int) Request::get('contact_id'));

            if ($contact) {
                // Jeden użytkownik = jeden pracownik. Bez tego zmiana powiązania
                // zostawiała poprzedniego pracownika przypiętego do tego konta.
                Contact::where('user_id', $user->id)
                    ->where('id', '!=', $contact->id)
                    ->update(['user_id' => null]);

                $contact->user_id = $user->id;
                $contact->save();
            }
        }

        return Redirect::back()->with('success', 'Użytkownik poprawiony.');
    }

    public function destroy(User $user)
    {
        if (App::environment('demo') && $user->isDemoUser()) {
            return Redirect::back()->with('error', 'Deleting the demo user is not allowed.');
        }

        $user->delete();

        return Redirect::back()->with('success', 'User deleted.');
    }

    public function restore(User $user)
    {
        $user->restore();

        return Redirect::back()->with('success', 'Obiekt przywrócony.');
    }

    public function block(User $user)
    {
        $user->active = 0;
        $user->save();
        return Redirect::back()->with('success', 'Użytkownik zablokowany.');
    }

    public function unblock(User $user)
    {
        $user->active = 1;
        $user->save();
        return Redirect::back()->with('success', 'Użytkownik odblokowany.');
    }

    public function disconnect(User $user)
    {
        Contact::where('user_id', $user->id)->update(['user_id' => null]);

        return Redirect::back()->with('success', 'Użytkownik odłączony.');
    }

}
