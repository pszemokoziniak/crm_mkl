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

    public function store()
    {
        Request::validate([
            'first_name' => ['required', 'max:50'],
            'last_name' => ['required', 'max:50'],
            'email' => ['required', 'max:50', 'email', Rule::unique('users')],
            'password' => [
                'required',
                'min:8',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
            ],
            'owner' => ['required', 'max:10'],
            'contact_id' => ['nullable'],
            'photo' => ['nullable', 'image'],
        ],
            [
                'required'  => 'Pole jest wymagane.',
                'unique' => 'Nazwa użyta',
                'numeric' => 'Pole attribute może zawierać tylko cyfry',
                'password.regex' => 'Hasło musi zawierać dużą literę, znak specjalny, cyfrę',
                'password.min' => 'Hasło musi zawierać 8 znaków',
            ]
        );

        Auth::user()->account->users()->create([
            'first_name' => Request::get('first_name'),
            'last_name' => Request::get('last_name'),
            'email' => Request::get('email'),
            'password' => Request::get('password'),
            'owner' => Request::get('owner'),
            'contact_id' => Request::get('user_id'),
            'photo_path' => Request::file('photo') ? Request::file('photo')->store('users') : null,
        ]);

        Mail::send(new CreateUserPassword(Request::get('email'), Request::get('password')));

        return Redirect::route('users')->with('success', 'Użytkownik utworzony.');
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

        Request::validate([
            'first_name' => ['required', 'max:50'],
            'last_name' => ['required', 'max:50'],
            'email' => ['required', 'max:50', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => [
                'nullable',
                'min:8',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
            ],
//            'owner' => ['nullable'],
            'contact_id' => ['nullable'],
            'photo' => ['nullable', 'image'],
        ],
        [
            'required'  => 'Pole jest wymagane.',
            'unique' => 'Nazwa użyta',
            'numeric' => 'Pole :attribute może zawierać tylko cyfry',
            'password.regex' => 'Hasło musi zawierać dużą literę, znak specjalny, cyfrę',
            'password.min' => 'Hasło musi zawierać 8 znaków',
        ]
        );

        $user->update(Request::only('first_name', 'last_name', 'email', 'owner' ));

        if (Request::file('photo')) {
            $user->update(['photo_path' => Request::file('photo')->store('users')]);
        }

        if (Request::get('password')) {
            $user->update(['password' => Request::get('password')]);
        }

        if (Request::get('contact_id') !== null) {
            $data = Contact::where('id', (int) Request::get('contact_id'))->first();
            $data->user_id = $user->id;
            $data->save();
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
