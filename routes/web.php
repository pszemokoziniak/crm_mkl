<?php

use App\Http\Controllers\A1Controller;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BadaniaController;
use App\Http\Controllers\BadaniaTypController;
use App\Http\Controllers\BhpController;
use App\Http\Controllers\BhpTypController;
use App\Http\Controllers\BudowaPracownicyController;
use App\Http\Controllers\BuildingTimeSheet;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\CtnDocumentsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumentyTypController;
use App\Http\Controllers\FeastsController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\JezykController;
use App\Http\Controllers\JezykTypController;
use App\Http\Controllers\KlientController;
use App\Http\Controllers\KrajTypController;
use App\Http\Controllers\NarzedziaController;
use App\Http\Controllers\NarzedziaTypController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\PbiozController;
use App\Http\Controllers\PrognozaController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ShiftStatusController;
use App\Http\Controllers\ToolWorkDatesController;
use App\Http\Controllers\UprawnieniaController;
use App\Http\Controllers\UprawnieniaTypController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\FunkcjaController;


use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth

Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store')
    ->middleware('guest');

Route::delete('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::get('password/expired', [AuthenticatedSessionController::class, 'expired'])
    ->name('password.expired')
    ->middleware('auth');


Route::post('password/expired', [AuthenticatedSessionController::class, 'postExpired'])
    ->name('password.expired.post')
    ->middleware('auth');


// Dashboard

Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth', 'password_expired');

// Users

Route::get('users', [UsersController::class, 'index'])
    ->name('users')
    ->middleware('auth', 'biuro-permission');

Route::get('users/create', [UsersController::class, 'create'])
    ->name('users.create')
    ->middleware('auth', 'biuro-permission');

Route::post('users', [UsersController::class, 'store'])
    ->name('users.store')
    ->middleware('auth', 'biuro-permission');

Route::get('users/{user}/edit', [UsersController::class, 'edit'])
    ->name('users.edit')
    ->middleware('auth', 'biuro-kierownik-permission');

Route::put('users/{user}', [UsersController::class, 'update'])
    ->name('users.update')
    ->middleware('auth', 'biuro-kierownik-permission');

Route::delete('users/{user}', [UsersController::class, 'destroy'])
    ->name('users.destroy')
    ->middleware('auth', 'biuro-permission');

Route::put('users/{user}/restore', [UsersController::class, 'restore'])
    ->name('users.restore')
    ->middleware('auth', 'biuro-permission');

Route::post('users/{user}/block', [UsersController::class, 'block'])
    ->name('users.block')
    ->middleware('auth', 'biuro-permission');

Route::post('users/{user}/unblock', [UsersController::class, 'unblock'])
    ->name('users.unblock')
    ->middleware('auth', 'biuro-permission');

// Organizations

Route::get('budowy', [OrganizationsController::class, 'index'])
    ->name('organizations')
    ->middleware('auth', 'biuro-permission');

Route::get('budowy/create', [OrganizationsController::class, 'create'])
    ->name('organizations.create')
    ->middleware('auth', 'biuro-permission');

Route::post('budowy', [OrganizationsController::class, 'store'])
    ->name('organizations.store')
    ->middleware('auth', 'biuro-permission');

Route::get('budowy/{organization}/edit', [OrganizationsController::class, 'edit'])
    ->name('organizations.edit')
    ->middleware('auth', 'biuro-kierownik-permission');

Route::put('budowy/{organization}', [OrganizationsController::class, 'update'])
    ->name('organizations.update')
    ->middleware('auth', 'biuro-permission');

Route::delete('budowy/{organization}', [OrganizationsController::class, 'destroy'])
    ->name('organizations.destroy')
    ->middleware('auth', 'biuro-permission');

Route::put('budowy/{organization}/restore', [OrganizationsController::class, 'restore'])
    ->name('organizations.restore')
    ->middleware('auth', 'biuro-permission');

// Klient Budowa

Route::get('budowy/{organization}/klient', [KlientController::class, 'index'])
    ->name('klient.index')
        ->middleware('auth', 'biuro-permission');


Route::get('budowy/{organization}/klient/create', [KlientController::class, 'create'])
    ->name('klient.create')
        ->middleware('auth', 'biuro-permission');


Route::post('klient/', [KlientController::class, 'store'])
    ->name('klient.store')
        ->middleware('auth', 'biuro-permission');


Route::get('budowy/{organization}/klient/{klient}/edit', [KlientController::class, 'edit'])
    ->name('klient.edit')
        ->middleware('auth', 'biuro-permission');


Route::put('klient/{klient}', [KlientController::class, 'update'])
    ->name('klient.update')
        ->middleware('auth', 'biuro-permission');


Route::delete('klient/{klient}/delete', [KlientController::class, 'destroy'])
    ->name('klient.destroy')
        ->middleware('auth', 'biuro-permission');


Route::put('budowy/{klient}/restore', [KlientController::class, 'restore'])
    ->name('klient.restore')
        ->middleware('auth', 'biuro-permission');

//Prognoza pracowników na budowach

Route::get('prognoza', [PrognozaController::class, 'index'])
    ->name('prognoza')
    ->middleware('auth', 'biuro-permission');

Route::get('prognoza/create', [PrognozaController::class, 'create'])
    ->name('prognoza.create')
    ->middleware('auth', 'biuro-permission');

Route::post('prognoza', [PrognozaController::class, 'store'])
    ->name('prognoza.store')
    ->middleware('auth', 'biuro-permission');

Route::get('prognoza/{prognoza}/edit', [PrognozaController::class, 'edit'])
    ->name('prognoza.edit')
    ->middleware('auth', 'biuro-permission');

Route::get('prognoza/building', [PrognozaController::class, 'building'])
    ->name('prognoza.building')
    ->middleware('auth', 'biuro-permission');

Route::put('prognoza/{prognoza}', [PrognozaController::class, 'update'])
    ->name('prognoza.update')
    ->middleware('auth', 'biuro-permission');

//Route::get('prognoza/list', [PrognozaController::class, 'displayWeeksInYear'])
//    ->name('prognoza.list')
//    ->middleware('auth', 'biuro-permission');


/// Contacts

Route::get('contacts', [ContactsController::class, 'index'])
    ->name('contacts')
        ->middleware('auth', 'biuro-permission');


Route::get('contacts/create', [ContactsController::class, 'create'])
    ->name('contacts.create')
        ->middleware('auth', 'biuro-permission');


Route::post('contacts', [ContactsController::class, 'store'])
    ->name('contacts.store')
        ->middleware('auth', 'biuro-permission');


Route::get('contacts/{contact}/edit', [ContactsController::class, 'edit'])
    ->name('contacts.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('contacts/{contact}', [ContactsController::class, 'update'])
    ->name('contacts.update')
        ->middleware('auth', 'biuro-permission');


Route::delete('contacts/{contact}', [ContactsController::class, 'destroy'])
    ->name('contacts.destroy')
        ->middleware('auth', 'biuro-permission');


Route::put('contacts/{contact}/restore', [ContactsController::class, 'restore'])
    ->name('contacts.restore')
        ->middleware('auth', 'biuro-permission');


// Narzedzia

Route::get('narzedzia', [NarzedziaController::class, 'index'])
    ->name('narzedzia')
        ->middleware('auth', 'biuro-permission');


Route::get('narzedzia/create', [NarzedziaController::class, 'create'])
    ->name('narzedzia.create')
        ->middleware('auth', 'biuro-permission');


Route::post('narzedzia', [NarzedziaController::class, 'store'])
    ->name('narzedzia.store')
        ->middleware('auth', 'biuro-permission');


Route::get('narzedzia/{narzedzia}/edit', [NarzedziaController::class, 'edit'])
    ->name('narzedzia.edit')
        ->middleware('auth', 'biuro-permission');


Route::post('narzedzia/{narzedzia}', [NarzedziaController::class, 'update'])
    ->name('narzedzia.update')
        ->middleware('auth', 'biuro-permission');


Route::delete('narzedzia/{narzedzia}', [NarzedziaController::class, 'destroy'])
    ->name('narzedzia.destroy')
        ->middleware('auth', 'biuro-permission');


Route::put('narzedzia/{narzedzia}/restore', [NarzedziaController::class, 'restore'])
    ->name('narzedzia.restore')
    ->middleware('auth', 'biuro-permission');

Route::delete('narzedzia/{narzedzia}/file', [NarzedziaController::class, 'deleteToolFile'])
    ->name('narzedzia.delete.document')
    ->middleware('auth', 'biuro-permission');


Route::get('narzedzia/{narzedzia}/file/{name}', [NarzedziaController::class, 'download'])
    ->name('narzedzia.download.file')
    ->middleware('auth', 'biuro-permission');
// Holidays

Route::get('contacts/{contact}/holiday', [HolidayController::class, 'index'])
    ->name('holiday.index')
    ->middleware('auth', 'admin-permission');


Route::get('contacts/{contact}/holiday/create', [HolidayController::class, 'create'])
    ->name('holiday.create')
    ->middleware('auth', 'admin-permission');


Route::post('holiday/{contact_id}', [HolidayController::class, 'store'])
    ->name('holiday.store')
    ->middleware('auth', 'admin-permission');


Route::get('contacts/{contact}/holiday/{holiday}/edit', [HolidayController::class, 'edit'])
    ->name('holiday.edit')
    ->middleware('auth', 'admin-permission');


Route::put('contacts/{contact}/holiday/{holiday}', [HolidayController::class, 'update'])
    ->name('holiday.update')
    ->middleware('auth', 'admin-permission');


Route::delete('holiday/{holiday}', [HolidayController::class, 'destroy'])
    ->name('holiday.destroy')
    ->middleware('auth', 'admin-permission');


Route::put('holiday/{holiday}/restore', [HolidayController::class, 'restore'])
    ->name('holiday.restore')
    ->middleware('auth', 'admin-permission');

//  Budowa Narzedzia

Route::get('budowy/{organization}/narzedzia', [ToolWorkDatesController::class, 'index'])
    ->name('budowy.narzedzia')
        ->middleware('auth', 'biuro-permission');


Route::get('budowy/{organization}/narzedzia/create', [ToolWorkDatesController::class, 'create'])
    ->name('budowy.narzedzia.create')
        ->middleware('auth', 'biuro-permission');


Route::post('budowy/{organization}/narzedzia/create', [ToolWorkDatesController::class, 'find'])
    ->name('budowy.narzedzia.post')
        ->middleware('auth', 'biuro-permission');


Route::post('budowy/{organization}/narzedzia', [ToolWorkDatesController::class, 'store'])
    ->name('budowy.narzedzia.store')
        ->middleware('auth', 'biuro-permission');


Route::get('budowy/{organization}/narzedzia/{narzedzia}/edit', [ToolWorkDatesController::class, 'edit'])
    ->name('budowy.narzedzia.edit')
        ->middleware('auth', 'biuro-permission');


Route::put('budowy/{organization}/narzedzia/{narzedzia}', [ToolWorkDatesController::class, 'update'])
    ->name('budowy.narzedzia.update')
        ->middleware('auth', 'biuro-permission');


Route::delete('budowy/{organization}/narzedzia/{toolWorkDate}/destroy', [ToolWorkDatesController::class, 'destroy'])
    ->name('budowy.narzedzia.destroy')
        ->middleware('auth', 'biuro-permission');


Route::put('budowy/{organization}/narzedzia/{narzedzia}/restore', [ToolWorkDatesController::class, 'restore'])
    ->name('budowy.narzedzia.restore')
        ->middleware('auth', 'biuro-permission');


// Dokumenty Typ

Route::get('dokumentyTyp', [DokumentyTypController::class, 'index'])
    ->name('dokumentyTyp')
        ->middleware('auth', 'admin-permission');


Route::get('dokumentyTyp/create', [DokumentyTypController::class, 'create'])
    ->name('dokumentyTyp.create')
        ->middleware('auth', 'admin-permission');


Route::post('dokumentyTyp', [DokumentyTypController::class, 'store'])
    ->name('dokumentyTyp.store')
        ->middleware('auth', 'admin-permission');


Route::get('dokumentyTyp/{dokumentyTyp}/edit', [DokumentyTypController::class, 'edit'])
    ->name('dokumentyTyp.edit')
        ->middleware('auth', 'admin-permission');


Route::put('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'update'])
    ->name('dokumentyTyp.update')
        ->middleware('auth', 'admin-permission');


Route::delete('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'destroy'])
    ->name('dokumentyTyp.destroy')
        ->middleware('auth', 'admin-permission');


Route::put('dokumentyTyp/{account}/restore', [DokumentyTypController::class, 'restore'])
    ->name('dokumentyTyp.restore')
        ->middleware('auth', 'admin-permission');


// Pracownicy budowa

Route::get('pracownicy/{organization}', [BudowaPracownicyController::class, 'index'])
    ->name('pracownicy.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('pracownicy/{contactWorkDate}', [BudowaPracownicyController::class, 'update'])
    ->name('pracownicy.update')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('pracownicy/{organization}/create', [BudowaPracownicyController::class, 'find'])
    ->name('pracownicy.create.post')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('pracownicy/{organization}/create', [BudowaPracownicyController::class, 'create'])
    ->name('pracownicy.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('pracownicy/{organization}/edit/{contactWorkDate}', [BudowaPracownicyController::class, 'edit'])
    ->name('pracownicy.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('pracownicy/{organization}', [BudowaPracownicyController::class, 'store'])
    ->name('pracownicy.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('pracownicy/{contactWorkDate}', [BudowaPracownicyController::class, 'destroy'])
    ->name('pracownicy.destroy')
        ->middleware('auth', 'biuro-permission');


Route::put('pracownicy/destroystore', [BudowaPracownicyController::class, 'destroyStore'])
    ->name('pracownicy.destroystore')
        ->middleware('auth', 'biuro-permission');


// Destroy pracownicy budowa
Route::get('contacts/{contact}/budowa/destroy', [ContactsController::class, 'destroyPracownikBudowa'])
    ->name('contacts.destroyPracownikBudowa')
        ->middleware('auth', 'biuro-permission');


// Badania

Route::get('contacts/{contact}/badania', [BadaniaController::class, 'index'])
    ->name('badania.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/badania/create', [BadaniaController::class, 'create'])
    ->name('badania.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('badania/{contact_id}', [BadaniaController::class, 'store'])
    ->name('badania.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/badania/{badania}/edit', [BadaniaController::class, 'edit'])
    ->name('badania.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('contacts/{contact}/badania/{badania}', [BadaniaController::class, 'update'])
    ->name('badania.update')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('badania/{badania}', [BadaniaController::class, 'destroy'])
    ->name('badania.destroy')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('badania/{badania}/restore', [BadaniaController::class, 'restore'])
    ->name('badania.restore')
        ->middleware('auth', 'biuro-kierownik-permission');


// BHP

Route::get('contacts/{contact}/bhp', [BhpController::class, 'index'])
    ->name('bhp.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/bhp/create', [BhpController::class, 'create'])
    ->name('bhp.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('bhp/{contact_id}', [BhpController::class, 'store'])
    ->name('bhp.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/bhp/{bhp}/edit', [BhpController::class, 'edit'])
    ->name('bhp.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('contacts/{contact}/bhp/{bhp}', [BhpController::class, 'update'])
    ->name('bhp.update')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('bhp/{bhp}', [BhpController::class, 'destroy'])
    ->name('bhp.destroy')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('bhp/{bhp}/restore', [BhpController::class, 'restore'])
    ->name('bhp.restore')
        ->middleware('auth', 'biuro-kierownik-permission');


// Pbioz

Route::get('contacts/{contact}/pbioz', [PbiozController::class, 'index'])
    ->name('pbioz.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/pbioz/create', [PbiozController::class, 'create'])
    ->name('pbioz.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('pbioz/{contact_id}', [PbiozController::class, 'store'])
    ->name('pbioz.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/pbioz/{pbioz}/edit', [PbiozController::class, 'edit'])
    ->name('pbioz.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('contacts/{contact}/pbioz/{pbioz}', [PbiozController::class, 'update'])
    ->name('pbioz.update')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('pbioz/{pbioz}', [PbiozController::class, 'destroy'])
    ->name('pbioz.destroy')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('pbioz/{pbioz}/restore', [PbiozController::class, 'restore'])
    ->name('pbioz.restore')
        ->middleware('auth', 'biuro-kierownik-permission');


// A1

Route::get('contacts/{contact}/a1', [A1Controller::class, 'index'])
    ->name('a1.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/a1/create', [A1Controller::class, 'create'])
    ->name('a1.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('a1/{contact_id}', [A1Controller::class, 'store'])
    ->name('a1.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/a1/{a1}/edit', [A1Controller::class, 'edit'])
    ->name('a1.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('contacts/{contact}/a1/{a1}', [A1Controller::class, 'update'])
    ->name('a1.update')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('a1/{a1}', [A1Controller::class, 'destroy'])
    ->name('a1.destroy')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('a1/{a1}/restore', [A1Controller::class, 'restore'])
    ->name('a1.restore')
        ->middleware('auth', 'biuro-kierownik-permission');


// Uprawnienia

Route::get('contacts/{contact}/uprawnienia', [UprawnieniaController::class, 'index'])
    ->name('uprawnienia.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/uprawnienia/create', [UprawnieniaController::class, 'create'])
    ->name('uprawnienia.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('uprawnienia/{contact_id}', [UprawnieniaController::class, 'store'])
    ->name('uprawnienia.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/uprawnienia/{uprawnienia}/edit', [UprawnieniaController::class, 'edit'])
    ->name('uprawnienia.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('contacts/{contact}/uprawnienia/{uprawnienia}', [UprawnieniaController::class, 'update'])
    ->name('uprawnienia.update')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('uprawnienia/{uprawnienia}', [UprawnieniaController::class, 'destroy'])
    ->name('uprawnienia.destroy')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('uprawnienia/{uprawnienia}/restore', [UprawnieniaController::class, 'restore'])
    ->name('uprawnienia.restore')
        ->middleware('auth', 'biuro-kierownik-permission');

// Typ sprzętu

Route::get('narzedziaTyp', [NarzedziaTypController::class, 'index'])
    ->name('narzedziaTyp')
    ->middleware('auth', 'admin-permission');


Route::get('narzedziaTyp/create', [NarzedziaTypController::class, 'create'])
    ->name('narzedziaTyp.create')
    ->middleware('auth', 'admin-permission');


Route::post('narzedziaTyp', [NarzedziaTypController::class, 'store'])
    ->name('narzedziaTyp.store')
    ->middleware('auth', 'admin-permission');


Route::get('narzedziaTyp/{narzedziaTyp}/edit', [NarzedziaTypController::class, 'edit'])
    ->name('narzedziaTyp.edit')
    ->middleware('auth', 'admin-permission');


Route::put('narzedziaTyp/{narzedziaTyp}', [NarzedziaTypController::class, 'update'])
    ->name('narzedziaTyp.update')
    ->middleware('auth', 'admin-permission');


Route::delete('narzedziaTyp/{narzedziaTyp}', [NarzedziaTypController::class, 'destroy'])
    ->name('narzedziaTyp.destroy')
    ->middleware('auth', 'admin-permission');


Route::put('narzedziaTyp/{narzedziaTyp}/restore', [NarzedziaTypController::class, 'restore'])
    ->name('narzedziaTyp.restore')
    ->middleware('auth', 'admin-permission');

// Języki

Route::get('contacts/{contact}/jezyk', [JezykController::class, 'index'])
    ->name('jezyk.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/jezyk/create', [JezykController::class, 'create'])
    ->name('jezyk.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('jezyk/{contact_id}', [JezykController::class, 'store'])
    ->name('jezyk.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact}/jezyk/{jezyk}/edit', [JezykController::class, 'edit'])
    ->name('jezyk.edit')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('contacts/{contact}/jezyk/{jezyk}', [JezykController::class, 'update'])
    ->name('jezyk.update')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('jezyk/{jezyk}', [JezykController::class, 'destroy'])
    ->name('jezyk.destroy')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::put('jezyk/{jezyk}/restore', [JezykController::class, 'restore'])
    ->name('jezyk.restore')
        ->middleware('auth', 'biuro-kierownik-permission');


// Accounts

Route::get('position', [AccountsController::class, 'index'])
    ->name('position')
        ->middleware('auth', 'biuro-permission');


Route::get('position/create', [AccountsController::class, 'create'])
    ->name('position.create')
        ->middleware('auth', 'biuro-permission');


Route::post('position', [AccountsController::class, 'store'])
    ->name('position.store')
        ->middleware('auth', 'biuro-permission');


Route::get('position/{account}/edit', [AccountsController::class, 'edit'])
    ->name('position.edit')
        ->middleware('auth', 'biuro-permission');


Route::put('position/{account}', [AccountsController::class, 'update'])
    ->name('position.update')
        ->middleware('auth', 'biuro-permission');


Route::delete('position/{account}', [AccountsController::class, 'destroy'])
    ->name('position.destroy')
        ->middleware('auth', 'biuro-permission');


Route::put('position/{account}/restore', [AccountsController::class, 'restore'])
    ->name('position.restore')
        ->middleware('auth', 'biuro-permission');



// Funkcja Typ

Route::get('funkcja', [FunkcjaController::class, 'index'])
    ->name('funkcja')
        ->middleware('auth', 'admin-permission');


Route::get('funkcja/create', [FunkcjaController::class, 'create'])
    ->name('funkcja.create')
        ->middleware('auth', 'admin-permission');


Route::post('funkcja', [FunkcjaController::class, 'store'])
    ->name('funkcja.store')
        ->middleware('auth', 'admin-permission');


Route::get('funkcja/{funkcja}/edit', [FunkcjaController::class, 'edit'])
    ->name('funkcja.edit')
        ->middleware('auth', 'admin-permission');


Route::put('funkcja/{funkcja}', [FunkcjaController::class, 'update'])
    ->name('funkcja.update')
        ->middleware('auth', 'admin-permission');


Route::delete('funkcja/{funkcja}', [FunkcjaController::class, 'destroy'])
    ->name('funkcja.destroy')
        ->middleware('auth', 'admin-permission');


Route::put('funkcja/{account}/restore', [FunkcjaController::class, 'restore'])
    ->name('funkcja.restore')
        ->middleware('auth', 'admin-permission');


// Dokumenty Typ

Route::get('dokumentyTyp', [DokumentyTypController::class, 'index'])
    ->name('dokumentyTyp')
        ->middleware('auth', 'admin-permission');


Route::get('dokumentyTyp/create', [DokumentyTypController::class, 'create'])
    ->name('dokumentyTyp.create')
        ->middleware('auth', 'admin-permission');


Route::post('dokumentyTyp', [DokumentyTypController::class, 'store'])
    ->name('dokumentyTyp.store')
        ->middleware('auth', 'admin-permission');


Route::get('dokumentyTyp/{dokumentyTyp}/edit', [DokumentyTypController::class, 'edit'])
    ->name('dokumentyTyp.edit')
        ->middleware('auth', 'admin-permission');


Route::put('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'update'])
    ->name('dokumentyTyp.update')
        ->middleware('auth', 'admin-permission');


Route::delete('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'destroy'])
    ->name('dokumentyTyp.destroy')
        ->middleware('auth', 'admin-permission');


Route::put('dokumentyTyp/{account}/restore', [DokumentyTypController::class, 'restore'])
    ->name('dokumentyTyp.restore')
        ->middleware('auth', 'admin-permission');


// Badania Typ

Route::get('badaniaTyp', [BadaniaTypController::class, 'index'])
    ->name('badaniaTyp')
        ->middleware('auth', 'admin-permission');


Route::get('badaniaTyp/create', [BadaniaTypController::class, 'create'])
    ->name('badaniaTyp.create')
        ->middleware('auth', 'admin-permission');


Route::post('badaniaTyp', [BadaniaTypController::class, 'store'])
    ->name('badaniaTyp.store')
        ->middleware('auth', 'admin-permission');


Route::get('badaniaTyp/{badaniaTyp}/edit', [BadaniaTypController::class, 'edit'])
    ->name('badaniaTyp.edit')
        ->middleware('auth', 'admin-permission');


Route::put('badaniaTyp/{badaniaTyp}', [BadaniaTypController::class, 'update'])
    ->name('badaniaTyp.update')
        ->middleware('auth', 'admin-permission');


Route::delete('badaniaTyp/{badaniaTyp}', [BadaniaTypController::class, 'destroy'])
    ->name('badaniaTyp.destroy')
        ->middleware('auth', 'admin-permission');


Route::put('badaniaTyp/{badaniaTyp}/restore', [BadaniaTypController::class, 'restore'])
    ->name('badaniaTyp.restore')
        ->middleware('auth', 'admin-permission');


// BHP Typ

Route::get('bhpTyp', [BhpTypController::class, 'index'])
    ->name('bhpTyp')
        ->middleware('auth', 'admin-permission');


Route::get('bhpTyp/create', [BhpTypController::class, 'create'])
    ->name('bhpTyp.create')
        ->middleware('auth', 'admin-permission');


Route::post('bhpTyp', [BhpTypController::class, 'store'])
    ->name('bhpTyp.store')
        ->middleware('auth', 'admin-permission');


Route::get('bhpTyp/{bhpTyp}/edit', [BhpTypController::class, 'edit'])
    ->name('bhpTyp.edit')
        ->middleware('auth', 'admin-permission');


Route::put('bhpTyp/{bhpTyp}', [BhpTypController::class, 'update'])
    ->name('bhpTyp.update')
        ->middleware('auth', 'admin-permission');


Route::delete('bhpTyp/{bhpTyp}', [BhpTypController::class, 'destroy'])
    ->name('bhpTyp.destroy')
        ->middleware('auth', 'admin-permission');


Route::put('bhpTyp/{bhpTyp}/restore', [BhpTypController::class, 'restore'])
    ->name('bhpTyp.restore')
        ->middleware('auth', 'admin-permission');


// Uprawnienia Typ

Route::get('uprawnieniaTyp', [UprawnieniaTypController::class, 'index'])
    ->name('uprawnieniaTyp')
        ->middleware('auth', 'biuro-permission');


Route::get('uprawnieniaTyp/create', [UprawnieniaTypController::class, 'create'])
    ->name('uprawnieniaTyp.create')
        ->middleware('auth', 'biuro-permission');


Route::post('uprawnieniaTyp', [UprawnieniaTypController::class, 'store'])
    ->name('uprawnieniaTyp.store')
        ->middleware('auth', 'biuro-permission');


Route::get('uprawnieniaTyp/{uprawnieniaTyp}/edit', [UprawnieniaTypController::class, 'edit'])
    ->name('uprawnieniaTyp.edit')
        ->middleware('auth', 'biuro-permission');


Route::put('uprawnieniaTyp/{uprawnieniaTyp}', [UprawnieniaTypController::class, 'update'])
    ->name('uprawnieniaTyp.update')
        ->middleware('auth', 'biuro-permission');


Route::delete('uprawnieniaTyp/{uprawnieniaTyp}', [UprawnieniaTypController::class, 'destroy'])
    ->name('uprawnieniaTyp.destroy')
        ->middleware('auth', 'biuro-permission');


Route::put('uprawnieniaTyp/{uprawnieniaTyp}/restore', [UprawnieniaTypController::class, 'restore'])
    ->name('uprawnieniaTyp.restore')
        ->middleware('auth', 'biuro-permission');


// Języki

Route::get('jezykTyp', [JezykTypController::class, 'index'])
    ->name('jezykTyp')
        ->middleware('auth', 'admin-permission');


Route::get('jezykTyp/create', [JezykTypController::class, 'create'])
    ->name('jezykTyp.create')
        ->middleware('auth', 'admin-permission');


Route::post('jezykTyp', [JezykTypController::class, 'store'])
    ->name('jezykTyp.store')
        ->middleware('auth', 'admin-permission');


Route::get('jezykTyp/{jezykTyp}/edit', [JezykTypController::class, 'edit'])
    ->name('jezykTyp.edit')
        ->middleware('auth', 'admin-permission');


Route::put('jezykTyp/{jezykTyp}', [JezykTypController::class, 'update'])
    ->name('jezykTyp.update')
        ->middleware('auth', 'admin-permission');


Route::delete('jezykTyp/{jezykTyp}', [JezykTypController::class, 'destroy'])
    ->name('jezykTyp.destroy')
        ->middleware('auth', 'admin-permission');


Route::put('jezykTyp/{jezykTyp}/restore', [JezykTypController::class, 'restore'])
    ->name('jezykTyp.restore')
        ->middleware('auth', 'admin-permission');


// Kraje

Route::get('krajTyp', [KrajTypController::class, 'index'])
    ->name('krajTyp')
        ->middleware('auth', 'admin-permission');


Route::get('krajTyp/create', [KrajTypController::class, 'create'])
    ->name('krajTyp.create')
        ->middleware('auth', 'admin-permission');


Route::post('krajTyp', [KrajTypController::class, 'store'])
    ->name('krajTyp.store')
        ->middleware('auth', 'admin-permission');


Route::get('krajTyp/{krajTyp}/edit', [KrajTypController::class, 'edit'])
    ->name('krajTyp.edit')
        ->middleware('auth', 'admin-permission');


Route::put('krajTyp/{krajTyp}', [KrajTypController::class, 'update'])
    ->name('krajTyp.update')
        ->middleware('auth', 'admin-permission');


Route::delete('krajTyp/{krajTyp}', [KrajTypController::class, 'destroy'])
    ->name('krajTyp.destroy')
        ->middleware('auth', 'admin-permission');


Route::put('krajTyp/{krajTyp}/restore', [KrajTypController::class, 'restore'])
    ->name('krajTyp.restore')
        ->middleware('auth', 'admin-permission');


// ShiftStatus

Route::get('shiftStatusTyp', [ShiftStatusController::class, 'index'])
    ->name('shiftStatusTyp')
    ->middleware('auth', 'biuro-permission');

Route::get('shiftStatusTyp/create', [ShiftStatusController::class, 'create'])
    ->name('shiftStatusTyp.create')
    ->middleware('auth', 'biuro-permission');


Route::post('shiftStatusTyp', [ShiftStatusController::class, 'store'])
    ->name('shiftStatusTyp.store')
    ->middleware('auth', 'biuro-permission');


Route::get('shiftStatusTyp/{shiftStatus}/edit', [ShiftStatusController::class, 'edit'])
    ->name('shiftStatusTyp.edit')
    ->middleware('auth', 'biuro-permission');


Route::put('shiftStatusTyp/{shiftStatus}', [ShiftStatusController::class, 'update'])
    ->name('shiftStatusTyp.update')
    ->middleware('auth', 'biuro-permission');


Route::delete('shiftStatusTyp/{shiftStatus}', [ShiftStatusController::class, 'destroy'])
    ->name('shiftStatusTyp.destroy')
    ->middleware('auth', 'biuro-permission');


Route::put('shiftStatusTyp/{shiftStatus}/restore', [ShiftStatusController::class, 'restore'])
    ->name('shiftStatusTyp.restore')
    ->middleware('auth', 'biuro-permission');


// Reports

Route::get('reports', [ReportsController::class, 'index'])
    ->name('reports')
        ->middleware('auth', 'biuro-permission');


Route::get('reports/koniecUprawinien', [ReportsController::class, 'koniecUprawinien'])
    ->name('reports.koniecUprawinien')
        ->middleware('auth', 'biuro-permission');


// Tools

Route::get('tools', [ToolsController::class, 'index'])
    ->name('tools')
    ->middleware('auth', 'biuro-permission');


// Images
Route::get('/img/{path}', [ImagesController::class, 'show'])
    ->where('path', '.*')
    ->name('image');

// Dokumenty

Route::get('contacts/{contact_id}/documents/', [CtnDocumentsController::class, 'index'])
    ->name('documents.index')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact_id}/documents/create', [CtnDocumentsController::class, 'create'])
    ->name('documents.create')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::post('contacts/{contact_id}/documents/store', [CtnDocumentsController::class, 'store'])
    ->name('documents.store')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::get('contacts/{contact_id}/documents/{document_id}', [CtnDocumentsController::class, 'view'])
    ->name('documents.view')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('contacts/{contact_id}/documents/{document_id}', [CtnDocumentsController::class, 'delete'])
    ->name('documents.delete')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('contacts/{contact_id}/documents/{document_id}/lekarskie', [CtnDocumentsController::class, 'deleteLek'])
    ->name('documentsLek.delete')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('contacts/{contact_id}/documents/{document_id}/bhp', [CtnDocumentsController::class, 'deleteBhp'])
    ->name('documentsBhp.delete')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('contacts/{contact_id}/documents/{document_id}/uprawnienia', [CtnDocumentsController::class, 'deleteUpr'])
    ->name('documentsUpr.delete')
        ->middleware('auth', 'biuro-kierownik-permission');


Route::delete('contacts/{contact_id}/documents/{document_id}/a1', [CtnDocumentsController::class, 'deleteA1'])
    ->name('documentsA1.delete')
        ->middleware('auth', 'biuro-kierownik-permission');


/** Building time sheets */
Route::get('building/{build}/time-sheet', [BuildingTimeSheet::class, 'view'])
    ->name('workTimeSheet.view')
    ->middleware('auth');

Route::post('building/{build}/time-sheet', [BuildingTimeSheet::class, 'store'])
    ->name('workTimeSheet.store')
    ->middleware('auth');

Route::post('building/{build}/time-sheet/delete', [BuildingTimeSheet::class, 'delete'])
    ->name('workTimeSheet.delete')
    ->middleware('auth');

Route::get('building/{build}/time-sheet/export', [BuildingTimeSheet::class, 'excelExport'])
    ->name('workTimeSheet.excelExport')
    ->middleware('auth');

Route::get('building/time-sheet/general-report', [BuildingTimeSheet::class, 'buildsReport'])
    ->name('workTimeSheet.buildsReport')
    ->middleware('auth');

Route::get('building/time-sheet/month-report', [BuildingTimeSheet::class, 'reportIndex'])
    ->name('workTimeSheet.reportIndex')
    ->middleware('auth');

/** Country Feasts */
Route::get('country/{country}/feasts', [FeastsController::class, 'index'])
    ->name('country_feasts.index')
    ->middleware('auth');

Route::get('country/{country}/feasts/create', [FeastsController::class, 'create'])
    ->name('country_feasts.create')
    ->middleware('auth');

Route::get('country/{country}/feasts/{feast}', [FeastsController::class, 'edit'])
    ->name('country_feasts.edit')
    ->middleware('auth');

Route::post('country/{country}/feasts', [FeastsController::class, 'store'])
    ->name('country_feasts.store')
    ->middleware('auth');

Route::delete('country/{country}/feasts/{feast}/delete', [FeastsController::class, 'delete'])
    ->name('country_feasts.delete')
    ->middleware('auth');
