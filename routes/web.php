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
use App\Http\Controllers\BazaWiedzyController;
use App\Http\Controllers\GrupySprzetuController;
use App\Http\Controllers\LogowaniaController;
use App\Http\Controllers\PodszywanieController;
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
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\PbiozController;
use App\Http\Controllers\PrognozaController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StatystykiController;
use App\Http\Controllers\ShiftStatusController;
use App\Http\Controllers\ToolWorkDatesController;
use App\Http\Controllers\UprawnieniaController;
use App\Http\Controllers\UprawnieniaTypController;
use App\Http\Controllers\UmowyController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ZadaniaController;
use App\Http\Controllers\ZmianyKadroweController;
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

// Wejście administratora na cudze konto (do sprawdzania widoku danej osoby)
Route::post('users/{user}/wejdz-jako', [PodszywanieController::class, 'wejdz'])
    ->name('podszywanie.wejdz')
    ->middleware('auth', 'moze:uzytkownicy.wejdz_jako');

// Powrót — dostępny z konta, na które admin wszedł, więc bez wymogu roli.
Route::post('wroc-do-siebie', [PodszywanieController::class, 'wroc'])
    ->name('podszywanie.wroc')
    ->middleware('auth');

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
    ->middleware('auth', 'moze:uzytkownicy.lista');

Route::get('users/create', [UsersController::class, 'create'])
    ->name('users.create')
    ->middleware('auth', 'moze:uzytkownicy.zakladanie');

Route::post('users', [UsersController::class, 'store'])
    ->name('users.store')
    ->middleware('auth', 'moze:uzytkownicy.zakladanie');

Route::get('users/{user}/edit', [UsersController::class, 'edit'])
    ->name('users.edit')
    ->middleware('auth', 'wlasny-profil-lub:uzytkownicy.edycja');

Route::put('users/{user}', [UsersController::class, 'update'])
    ->name('users.update')
    ->middleware('auth', 'wlasny-profil-lub:uzytkownicy.edycja');

Route::delete('users/{user}', [UsersController::class, 'destroy'])
    ->name('users.destroy')
    ->middleware('auth', 'moze:uzytkownicy.usuwanie');

Route::put('users/{user}/restore', [UsersController::class, 'restore'])
    ->name('users.restore')
    ->middleware('auth', 'moze:uzytkownicy.usuwanie');

Route::post('users/{user}/block', [UsersController::class, 'block'])
    ->name('users.block')
    ->middleware('auth', 'moze:uzytkownicy.blokowanie');

Route::post('users/{user}/unblock', [UsersController::class, 'unblock'])
    ->name('users.unblock')
    ->middleware('auth', 'moze:uzytkownicy.blokowanie');

Route::post('users/{user}/disconnect', [UsersController::class, 'disconnect'])
    ->name('users.disconnect')
    ->middleware('auth', 'moze:uzytkownicy.edycja');

// Organizations

Route::get('budowy', [OrganizationsController::class, 'index'])
    ->name('organizations')
    ->middleware('auth', 'moze:budowy.podglad');

Route::get('budowy/create', [OrganizationsController::class, 'create'])
    ->name('organizations.create')
    ->middleware('auth', 'moze:budowy.zakladanie');

// Wyszukiwarka klientów z CRM (proxy do crm_mklv2)
Route::get('crm/klienci', [OrganizationsController::class, 'searchClients'])
    ->name('crm.klienci')
    ->middleware('auth', 'moze:budowy.edycja');

// Prognoza pracowników w kontekście budowy (podzakładka)
Route::get('budowy/{organization}/prognoza', [PrognozaController::class, 'budowaShow'])
    ->name('budowy.prognoza')
    ->middleware('auth', 'moze:prognoza.podglad');

Route::post('budowy/{organization}/prognoza', [PrognozaController::class, 'budowaStore'])
    ->name('budowy.prognoza.store')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::put('budowy/{organization}/prognoza/{prognoza}', [PrognozaController::class, 'budowaUpdate'])
    ->name('budowy.prognoza.update')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::delete('budowy/{organization}/prognoza/{prognoza}', [PrognozaController::class, 'budowaDestroy'])
    ->name('budowy.prognoza.destroy')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::post('budowy', [OrganizationsController::class, 'store'])
    ->name('organizations.store')
    ->middleware('auth', 'moze:budowy.zakladanie');

Route::get('budowy/{organization}/edit', [OrganizationsController::class, 'edit'])
    ->name('organizations.edit')
    ->middleware('auth', 'moze:budowy.podglad');

Route::put('budowy/{organization}', [OrganizationsController::class, 'update'])
    ->name('organizations.update')
    ->middleware('auth', 'moze:budowy.edycja');

Route::delete('budowy/{organization}', [OrganizationsController::class, 'destroy'])
    ->name('organizations.destroy')
    ->middleware('auth', 'moze:budowy.archiwizacja');

Route::put('budowy/{organization}/restore', [OrganizationsController::class, 'restore'])
    ->name('organizations.restore')
    ->middleware('auth', 'moze:budowy.archiwizacja');

// Klient Budowa

Route::get('budowy/{organization}/klient', [KlientController::class, 'index'])
    ->name('klient.index')
        ->middleware('auth', 'moze:budowy.podglad');


Route::get('budowy/{organization}/klient/create', [KlientController::class, 'create'])
    ->name('klient.create')
        ->middleware('auth', 'moze:budowy.edycja');


Route::post('klient/', [KlientController::class, 'store'])
    ->name('klient.store')
        ->middleware('auth', 'moze:budowy.edycja');


Route::get('budowy/{organization}/klient/{klient}/edit', [KlientController::class, 'edit'])
    ->name('klient.edit')
        ->middleware('auth', 'moze:budowy.edycja');


Route::put('klient/{klient}', [KlientController::class, 'update'])
    ->name('klient.update')
        ->middleware('auth', 'moze:budowy.edycja');


Route::delete('klient/{klient}/delete', [KlientController::class, 'destroy'])
    ->name('klient.destroy')
        ->middleware('auth', 'moze:budowy.edycja');


Route::put('budowy/{klient}/restore', [KlientController::class, 'restore'])
    ->name('klient.restore')
        ->middleware('auth', 'moze:budowy.archiwizacja');

// Ustawienia aplikacji
Route::get('ustawienia', [SettingsController::class, 'index'])
    ->name('ustawienia')
    ->middleware('auth', 'moze:slowniki.biura');

Route::put('ustawienia', [SettingsController::class, 'update'])
    ->name('ustawienia.update')
    ->middleware('auth', 'moze:slowniki.biura');

//Prognoza pracowników na budowach

Route::get('prognoza', [PrognozaController::class, 'index'])
    ->name('prognoza')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::get('prognoza/create', [PrognozaController::class, 'create'])
    ->name('prognoza.create')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::post('prognoza', [PrognozaController::class, 'store'])
    ->name('prognoza.store')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::get('prognoza/{prognoza}/edit', [PrognozaController::class, 'edit'])
    ->name('prognoza.edit')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::get('prognoza/building', [PrognozaController::class, 'building'])
    ->name('prognoza.building')
    ->middleware('auth', 'moze:prognoza.obsluga');

Route::put('prognoza/{prognoza}', [PrognozaController::class, 'update'])
    ->name('prognoza.update')
    ->middleware('auth', 'moze:prognoza.obsluga');

//Route::get('prognoza/list', [PrognozaController::class, 'displayWeeksInYear'])
//    ->name('prognoza.list')
//    ->middleware('auth', 'moze:prognoza.obsluga');


/// Contacts

Route::get('contacts', [ContactsController::class, 'index'])
    ->name('contacts')
        ->middleware('auth', 'moze:kartoteki.lista');

Route::get('kierownicy', [ContactsController::class, 'kierownicy'])
    ->name('kierownicy')
        ->middleware('auth', 'moze:kartoteki.lista');


Route::get('contacts/create', [ContactsController::class, 'create'])
    ->name('contacts.create')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::post('contacts', [ContactsController::class, 'store'])
    ->name('contacts.store')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::get('contacts/{contact}/edit', [ContactsController::class, 'edit'])
    ->name('contacts.edit')
        ->middleware('auth', 'moze:kartoteki.podglad');

// Przypisanie pracownika do budowy z jego profilu (usuwanie tylko w zakładce budowy)
Route::post('contacts/{contact}/przypisz-budowe', [ContactsController::class, 'przypiszBudowe'])
    ->name('contacts.przypisz-budowe')
    ->middleware('auth', 'moze:budowy.przypisywanie');


Route::post('contacts/{contact}', [ContactsController::class, 'update'])
    ->name('contacts.update')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::delete('contacts/{contact}', [ContactsController::class, 'destroy'])
    ->name('contacts.destroy')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::put('contacts/{contact}/restore', [ContactsController::class, 'restore'])
    ->name('contacts.restore')
        ->middleware('auth', 'moze:kartoteki.edycja');

Route::get('contacts/{contact}/history', [ContactsController::class, 'history'])
    ->name('contacts.history')
    ->middleware('auth', 'moze:kartoteki.podglad');


// Narzedzia

Route::get('narzedzia', [NarzedziaController::class, 'index'])
    ->name('narzedzia')
        ->middleware('auth', 'moze:sprzet.obsluga');


// Wydanie sprzętu na budowę i powrót do magazynu — wprost z listy magazynu.
Route::post('narzedzia/przypisz', [NarzedziaController::class, 'przypisz'])
    ->name('narzedzia.przypisz')
    ->middleware('auth', 'moze:sprzet.obsluga');

Route::delete('narzedzia/przypisanie/{toolWorkDate}', [NarzedziaController::class, 'zdejmij'])
    ->name('narzedzia.zdejmij')
    ->middleware('auth', 'moze:sprzet.obsluga');

Route::get('narzedzia/create', [NarzedziaController::class, 'create'])
    ->name('narzedzia.create')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::post('narzedzia', [NarzedziaController::class, 'store'])
    ->name('narzedzia.store')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::get('narzedzia/{narzedzia}/edit', [NarzedziaController::class, 'edit'])
    ->name('narzedzia.edit')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::post('narzedzia/{narzedzia}', [NarzedziaController::class, 'update'])
    ->name('narzedzia.update')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::delete('narzedzia/{narzedzia}', [NarzedziaController::class, 'destroy'])
    ->name('narzedzia.destroy')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::put('narzedzia/{narzedzia}/restore', [NarzedziaController::class, 'restore'])
    ->name('narzedzia.restore')
    ->middleware('auth', 'moze:sprzet.obsluga');

Route::delete('narzedzia/{narzedzia}/file', [NarzedziaController::class, 'deleteToolFile'])
    ->name('narzedzia.delete.document')
    ->middleware('auth', 'moze:sprzet.obsluga');


// Podpis pliku i wskazanie zdjęcia głównego karty sprzętu.
Route::put('narzedzia/{narzedzia}/pliki/{toolFile}', [NarzedziaController::class, 'aktualizujPlik'])
    ->name('narzedzia.pliki.update')
    ->middleware('auth', 'moze:sprzet.obsluga');

Route::delete('narzedzia/{narzedzia}/pliki/{toolFile}', [NarzedziaController::class, 'usunPlik'])
    ->name('narzedzia.pliki.destroy')
    ->middleware('auth', 'moze:sprzet.obsluga');

Route::get('narzedzia/{narzedzia}/file/{name}', [NarzedziaController::class, 'download'])
    ->name('narzedzia.download.file')
    ->middleware('auth', 'moze:sprzet.obsluga');
// Holidays

Route::get('contacts/{contact}/holiday', [HolidayController::class, 'index'])
    ->name('holiday.index')
    ->middleware('auth', 'moze:nieobecnosci.podglad');


Route::get('contacts/{contact}/holiday/create', [HolidayController::class, 'create'])
    ->name('holiday.create')
    ->middleware('auth', 'moze:nieobecnosci.dodawanie');


Route::post('holiday/{contact_id}', [HolidayController::class, 'store'])
    ->name('holiday.store')
    ->middleware('auth', 'moze:nieobecnosci.dodawanie');


Route::get('contacts/{contact}/holiday/{holiday}/edit', [HolidayController::class, 'edit'])
    ->name('holiday.edit')
    ->middleware('auth', 'moze:nieobecnosci.edycja');


Route::put('contacts/{contact}/holiday/{holiday}', [HolidayController::class, 'update'])
    ->name('holiday.update')
    ->middleware('auth', 'moze:nieobecnosci.edycja');


Route::delete('holiday/{holiday}', [HolidayController::class, 'destroy'])
    ->name('holiday.destroy')
    ->middleware('auth', 'moze:nieobecnosci.usuwanie');


Route::put('holiday/{holiday}/restore', [HolidayController::class, 'restore'])
    ->name('holiday.restore')
    ->middleware('auth', 'moze:nieobecnosci.usuwanie');

//  Budowa Narzedzia

Route::get('budowy/{organization}/narzedzia', [ToolWorkDatesController::class, 'index'])
    ->name('budowy.narzedzia')
        ->middleware('auth', 'moze:sprzet.podglad');


Route::get('budowy/{organization}/narzedzia/create', [ToolWorkDatesController::class, 'create'])
    ->name('budowy.narzedzia.create')
        ->middleware('auth', 'moze:sprzet.obsluga');




Route::post('budowy/{organization}/narzedzia', [ToolWorkDatesController::class, 'store'])
    ->name('budowy.narzedzia.store')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::get('budowy/{organization}/narzedzia/{narzedzia}/edit', [ToolWorkDatesController::class, 'edit'])
    ->name('budowy.narzedzia.edit')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::put('budowy/{organization}/narzedzia/{narzedzia}', [ToolWorkDatesController::class, 'update'])
    ->name('budowy.narzedzia.update')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::delete('budowy/{organization}/narzedzia/{toolWorkDate}/destroy', [ToolWorkDatesController::class, 'destroy'])
    ->name('budowy.narzedzia.destroy')
        ->middleware('auth', 'moze:sprzet.obsluga');


Route::put('budowy/{organization}/narzedzia/{narzedzia}/restore', [ToolWorkDatesController::class, 'restore'])
    ->name('budowy.narzedzia.restore')
        ->middleware('auth', 'moze:sprzet.obsluga');


// Dokumenty Typ

Route::get('dokumentyTyp', [DokumentyTypController::class, 'index'])
    ->name('dokumentyTyp')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('dokumentyTyp/create', [DokumentyTypController::class, 'create'])
    ->name('dokumentyTyp.create')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::post('dokumentyTyp', [DokumentyTypController::class, 'store'])
    ->name('dokumentyTyp.store')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('dokumentyTyp/{dokumentyTyp}/edit', [DokumentyTypController::class, 'edit'])
    ->name('dokumentyTyp.edit')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'update'])
    ->name('dokumentyTyp.update')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::delete('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'destroy'])
    ->name('dokumentyTyp.destroy')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('dokumentyTyp/{account}/restore', [DokumentyTypController::class, 'restore'])
    ->name('dokumentyTyp.restore')
        ->middleware('auth', 'moze:slowniki.systemowe');


// Pracownicy budowa

Route::get('pracownicy/{organization}', [BudowaPracownicyController::class, 'index'])
    ->name('pracownicy.index')
        ->middleware('auth', 'moze:budowy.podglad');


// Zbiorcze skrócenie pobytu — przy przenoszeniu ekipy na inną budowę.
Route::put('pracownicy/{organization}/data-konca', [BudowaPracownicyController::class, 'zbiorczaDataKonca'])
    ->name('pracownicy.data-konca')
    ->middleware('auth', 'moze:budowy.przypisywanie');

Route::put('pracownicy/{contactWorkDate}', [BudowaPracownicyController::class, 'update'])
    ->name('pracownicy.update')
        ->middleware('auth', 'moze:budowy.przypisywanie');


Route::post('pracownicy/{organization}/create', [BudowaPracownicyController::class, 'find'])
    ->name('pracownicy.create.post')
        ->middleware('auth', 'moze:budowy.przypisywanie');


Route::get('pracownicy/{organization}/create', [BudowaPracownicyController::class, 'create'])
    ->name('pracownicy.create')
        ->middleware('auth', 'moze:budowy.przypisywanie');


Route::get('pracownicy/{organization}/edit/{contactWorkDate}', [BudowaPracownicyController::class, 'edit'])
    ->name('pracownicy.edit')
        ->middleware('auth', 'moze:budowy.przypisywanie');


Route::post('pracownicy/{organization}', [BudowaPracownicyController::class, 'store'])
    ->name('pracownicy.store')
        ->middleware('auth', 'moze:budowy.przypisywanie');


Route::delete('pracownicy/{contactWorkDate}', [BudowaPracownicyController::class, 'destroy'])
    ->name('pracownicy.destroy')
        ->middleware('auth', 'moze:budowy.przypisywanie');


Route::put('pracownicy/destroystore', [BudowaPracownicyController::class, 'destroyStore'])
    ->name('pracownicy.destroystore')
        ->middleware('auth', 'moze:budowy.przypisywanie');


// Kierownictwo budowy
Route::get('budowy/{organization}/kierownictwo', [BudowaPracownicyController::class, 'management'])
    ->name('budowy.management')
    ->middleware('auth', 'moze:budowy.podglad');

Route::post('budowy/{organization}/kierownictwo', [BudowaPracownicyController::class, 'storeManagement'])
    ->name('budowy.management.store')
    ->middleware('auth', 'moze:budowy.przypisywanie');


// Destroy pracownicy budowa
Route::get('contacts/{contact}/budowa/destroy', [ContactsController::class, 'destroyPracownikBudowa'])
    ->name('contacts.destroyPracownikBudowa')
        ->middleware('auth', 'moze:budowy.przypisywanie');


// Badania

Route::get('contacts/{contact}/badania', [BadaniaController::class, 'index'])
    ->name('badania.index')
        ->middleware('auth', 'moze:dokumenty.podglad');


Route::get('contacts/{contact}/badania/create', [BadaniaController::class, 'create'])
    ->name('badania.create')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::post('badania/{contact_id}', [BadaniaController::class, 'store'])
    ->name('badania.store')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::get('contacts/{contact}/badania/{badania}/edit', [BadaniaController::class, 'edit'])
    ->name('badania.edit')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::put('contacts/{contact}/badania/{badania}', [BadaniaController::class, 'update'])
    ->name('badania.update')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::delete('badania/{badania}', [BadaniaController::class, 'destroy'])
    ->name('badania.destroy')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::put('badania/{badania}/restore', [BadaniaController::class, 'restore'])
    ->name('badania.restore')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


// BHP

Route::get('contacts/{contact}/bhp', [BhpController::class, 'index'])
    ->name('bhp.index')
        ->middleware('auth', 'moze:dokumenty.podglad');


Route::get('contacts/{contact}/bhp/create', [BhpController::class, 'create'])
    ->name('bhp.create')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::post('bhp/{contact_id}', [BhpController::class, 'store'])
    ->name('bhp.store')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::get('contacts/{contact}/bhp/{bhp}/edit', [BhpController::class, 'edit'])
    ->name('bhp.edit')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::put('contacts/{contact}/bhp/{bhp}', [BhpController::class, 'update'])
    ->name('bhp.update')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::delete('bhp/{bhp}', [BhpController::class, 'destroy'])
    ->name('bhp.destroy')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::put('bhp/{bhp}/restore', [BhpController::class, 'restore'])
    ->name('bhp.restore')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


// Pbioz

Route::get('contacts/{contact}/pbioz', [PbiozController::class, 'index'])
    ->name('pbioz.index')
        ->middleware('auth', 'moze:dokumenty.podglad');


Route::get('contacts/{contact}/pbioz/create', [PbiozController::class, 'create'])
    ->name('pbioz.create')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::post('pbioz/{contact_id}', [PbiozController::class, 'store'])
    ->name('pbioz.store')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::get('contacts/{contact}/pbioz/{pbioz}/edit', [PbiozController::class, 'edit'])
    ->name('pbioz.edit')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::put('contacts/{contact}/pbioz/{pbioz}', [PbiozController::class, 'update'])
    ->name('pbioz.update')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::delete('pbioz/{pbioz}', [PbiozController::class, 'destroy'])
    ->name('pbioz.destroy')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::put('pbioz/{pbioz}/restore', [PbiozController::class, 'restore'])
    ->name('pbioz.restore')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


// A1

Route::get('contacts/{contact}/a1', [A1Controller::class, 'index'])
    ->name('a1.index')
        ->middleware('auth', 'moze:dokumenty.podglad');


Route::get('contacts/{contact}/a1/create', [A1Controller::class, 'create'])
    ->name('a1.create')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::post('a1/{contact_id}', [A1Controller::class, 'store'])
    ->name('a1.store')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::get('contacts/{contact}/a1/{a1}/edit', [A1Controller::class, 'edit'])
    ->name('a1.edit')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::put('contacts/{contact}/a1/{a1}', [A1Controller::class, 'update'])
    ->name('a1.update')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::delete('a1/{a1}', [A1Controller::class, 'destroy'])
    ->name('a1.destroy')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::put('a1/{a1}/restore', [A1Controller::class, 'restore'])
    ->name('a1.restore')
        ->middleware('auth', 'moze:dokumenty.usuwanie');

// Budowa A1
Route::get('budowy/{organization}/a1', [BudowaPracownicyController::class, 'a1Index'])
    ->name('budowy.a1.index')
    ->middleware('auth', 'moze:budowy.podglad');


// Uprawnienia

Route::get('contacts/{contact}/uprawnienia', [UprawnieniaController::class, 'index'])
    ->name('uprawnienia.index')
        ->middleware('auth', 'moze:dokumenty.podglad');


Route::get('contacts/{contact}/uprawnienia/create', [UprawnieniaController::class, 'create'])
    ->name('uprawnienia.create')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::post('uprawnienia/{contact_id}', [UprawnieniaController::class, 'store'])
    ->name('uprawnienia.store')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::get('contacts/{contact}/uprawnienia/{uprawnienia}/edit', [UprawnieniaController::class, 'edit'])
    ->name('uprawnienia.edit')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::put('contacts/{contact}/uprawnienia/{uprawnienia}', [UprawnieniaController::class, 'update'])
    ->name('uprawnienia.update')
        ->middleware('auth', 'moze:dokumenty.edycja');


Route::delete('uprawnienia/{uprawnienia}', [UprawnieniaController::class, 'destroy'])
    ->name('uprawnienia.destroy')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::put('uprawnienia/{uprawnienia}/restore', [UprawnieniaController::class, 'restore'])
    ->name('uprawnienia.restore')
        ->middleware('auth', 'moze:dokumenty.usuwanie');

// Typ sprzętu

Route::get('narzedziaTyp', [NarzedziaTypController::class, 'index'])
    ->name('narzedziaTyp')
    ->middleware('auth', 'moze:slowniki.biura');


Route::get('narzedziaTyp/create', [NarzedziaTypController::class, 'create'])
    ->name('narzedziaTyp.create')
    ->middleware('auth', 'moze:slowniki.biura');


Route::post('narzedziaTyp', [NarzedziaTypController::class, 'store'])
    ->name('narzedziaTyp.store')
    ->middleware('auth', 'moze:slowniki.biura');


Route::get('narzedziaTyp/{narzedziaTyp}/edit', [NarzedziaTypController::class, 'edit'])
    ->name('narzedziaTyp.edit')
    ->middleware('auth', 'moze:slowniki.biura');


Route::put('narzedziaTyp/{narzedziaTyp}', [NarzedziaTypController::class, 'update'])
    ->name('narzedziaTyp.update')
    ->middleware('auth', 'moze:slowniki.biura');


Route::delete('narzedziaTyp/{narzedziaTyp}', [NarzedziaTypController::class, 'destroy'])
    ->name('narzedziaTyp.destroy')
    ->middleware('auth', 'moze:slowniki.biura');


Route::put('narzedziaTyp/{narzedziaTyp}/restore', [NarzedziaTypController::class, 'restore'])
    ->name('narzedziaTyp.restore')
    ->middleware('auth', 'moze:slowniki.biura');

// Języki

Route::get('contacts/{contact}/jezyk', [JezykController::class, 'index'])
    ->name('jezyk.index')
        ->middleware('auth', 'moze:kartoteki.podglad');


Route::get('contacts/{contact}/jezyk/create', [JezykController::class, 'create'])
    ->name('jezyk.create')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::post('jezyk/{contact_id}', [JezykController::class, 'store'])
    ->name('jezyk.store')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::get('contacts/{contact}/jezyk/{jezyk}/edit', [JezykController::class, 'edit'])
    ->name('jezyk.edit')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::put('contacts/{contact}/jezyk/{jezyk}', [JezykController::class, 'update'])
    ->name('jezyk.update')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::delete('jezyk/{jezyk}', [JezykController::class, 'destroy'])
    ->name('jezyk.destroy')
        ->middleware('auth', 'moze:kartoteki.edycja');


Route::put('jezyk/{jezyk}/restore', [JezykController::class, 'restore'])
    ->name('jezyk.restore')
        ->middleware('auth', 'moze:kartoteki.edycja');


// Accounts

Route::get('position', [AccountsController::class, 'index'])
    ->name('position')
        ->middleware('auth', 'moze:slowniki.biura');


Route::get('position/create', [AccountsController::class, 'create'])
    ->name('position.create')
        ->middleware('auth', 'moze:slowniki.biura');


Route::post('position', [AccountsController::class, 'store'])
    ->name('position.store')
        ->middleware('auth', 'moze:slowniki.biura');


Route::get('position/{account}/edit', [AccountsController::class, 'edit'])
    ->name('position.edit')
        ->middleware('auth', 'moze:slowniki.biura');


Route::put('position/{account}', [AccountsController::class, 'update'])
    ->name('position.update')
        ->middleware('auth', 'moze:slowniki.biura');


Route::delete('position/{account}', [AccountsController::class, 'destroy'])
    ->name('position.destroy')
        ->middleware('auth', 'moze:slowniki.biura');


Route::put('position/{account}/restore', [AccountsController::class, 'restore'])
    ->name('position.restore')
        ->middleware('auth', 'moze:slowniki.biura');



// Funkcja Typ

Route::get('funkcja', [FunkcjaController::class, 'index'])
    ->name('funkcja')
        ->middleware('auth', 'moze:slowniki.biura');


Route::get('funkcja/create', [FunkcjaController::class, 'create'])
    ->name('funkcja.create')
        ->middleware('auth', 'moze:slowniki.biura');


Route::post('funkcja', [FunkcjaController::class, 'store'])
    ->name('funkcja.store')
        ->middleware('auth', 'moze:slowniki.biura');


Route::get('funkcja/{funkcja}/edit', [FunkcjaController::class, 'edit'])
    ->name('funkcja.edit')
        ->middleware('auth', 'moze:slowniki.biura');


Route::put('funkcja/{funkcja}', [FunkcjaController::class, 'update'])
    ->name('funkcja.update')
        ->middleware('auth', 'moze:slowniki.biura');


Route::delete('funkcja/{funkcja}', [FunkcjaController::class, 'destroy'])
    ->name('funkcja.destroy')
        ->middleware('auth', 'moze:slowniki.biura');



// Dokumenty Typ

Route::get('dokumentyTyp', [DokumentyTypController::class, 'index'])
    ->name('dokumentyTyp')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('dokumentyTyp/create', [DokumentyTypController::class, 'create'])
    ->name('dokumentyTyp.create')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::post('dokumentyTyp', [DokumentyTypController::class, 'store'])
    ->name('dokumentyTyp.store')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('dokumentyTyp/{dokumentyTyp}/edit', [DokumentyTypController::class, 'edit'])
    ->name('dokumentyTyp.edit')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'update'])
    ->name('dokumentyTyp.update')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::delete('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'destroy'])
    ->name('dokumentyTyp.destroy')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('dokumentyTyp/{account}/restore', [DokumentyTypController::class, 'restore'])
    ->name('dokumentyTyp.restore')
        ->middleware('auth', 'moze:slowniki.systemowe');


// Badania Typ

Route::get('badaniaTyp', [BadaniaTypController::class, 'index'])
    ->name('badaniaTyp')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('badaniaTyp/create', [BadaniaTypController::class, 'create'])
    ->name('badaniaTyp.create')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::post('badaniaTyp', [BadaniaTypController::class, 'store'])
    ->name('badaniaTyp.store')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('badaniaTyp/{badaniaTyp}/edit', [BadaniaTypController::class, 'edit'])
    ->name('badaniaTyp.edit')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('badaniaTyp/{badaniaTyp}', [BadaniaTypController::class, 'update'])
    ->name('badaniaTyp.update')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::delete('badaniaTyp/{badaniaTyp}', [BadaniaTypController::class, 'destroy'])
    ->name('badaniaTyp.destroy')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('badaniaTyp/{badaniaTyp}/restore', [BadaniaTypController::class, 'restore'])
    ->name('badaniaTyp.restore')
        ->middleware('auth', 'moze:slowniki.systemowe');


// BHP Typ

Route::get('bhpTyp', [BhpTypController::class, 'index'])
    ->name('bhpTyp')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('bhpTyp/create', [BhpTypController::class, 'create'])
    ->name('bhpTyp.create')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::post('bhpTyp', [BhpTypController::class, 'store'])
    ->name('bhpTyp.store')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('bhpTyp/{bhpTyp}/edit', [BhpTypController::class, 'edit'])
    ->name('bhpTyp.edit')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('bhpTyp/{bhpTyp}', [BhpTypController::class, 'update'])
    ->name('bhpTyp.update')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::delete('bhpTyp/{bhpTyp}', [BhpTypController::class, 'destroy'])
    ->name('bhpTyp.destroy')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('bhpTyp/{bhpTyp}/restore', [BhpTypController::class, 'restore'])
    ->name('bhpTyp.restore')
        ->middleware('auth', 'moze:slowniki.systemowe');


// Uprawnienia Typ

Route::get('uprawnieniaTyp', [UprawnieniaTypController::class, 'index'])
    ->name('uprawnieniaTyp')
        ->middleware('auth', 'moze:slowniki.biura');


Route::get('uprawnieniaTyp/create', [UprawnieniaTypController::class, 'create'])
    ->name('uprawnieniaTyp.create')
        ->middleware('auth', 'moze:slowniki.biura');


Route::post('uprawnieniaTyp', [UprawnieniaTypController::class, 'store'])
    ->name('uprawnieniaTyp.store')
        ->middleware('auth', 'moze:slowniki.biura');


Route::get('uprawnieniaTyp/{uprawnieniaTyp}/edit', [UprawnieniaTypController::class, 'edit'])
    ->name('uprawnieniaTyp.edit')
        ->middleware('auth', 'moze:slowniki.biura');


Route::put('uprawnieniaTyp/{uprawnieniaTyp}', [UprawnieniaTypController::class, 'update'])
    ->name('uprawnieniaTyp.update')
        ->middleware('auth', 'moze:slowniki.biura');


Route::delete('uprawnieniaTyp/{uprawnieniaTyp}', [UprawnieniaTypController::class, 'destroy'])
    ->name('uprawnieniaTyp.destroy')
        ->middleware('auth', 'moze:slowniki.biura');


Route::put('uprawnieniaTyp/{uprawnieniaTyp}/restore', [UprawnieniaTypController::class, 'restore'])
    ->name('uprawnieniaTyp.restore')
        ->middleware('auth', 'moze:slowniki.biura');


// Języki

Route::get('jezykTyp', [JezykTypController::class, 'index'])
    ->name('jezykTyp')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('jezykTyp/create', [JezykTypController::class, 'create'])
    ->name('jezykTyp.create')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::post('jezykTyp', [JezykTypController::class, 'store'])
    ->name('jezykTyp.store')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('jezykTyp/{jezykTyp}/edit', [JezykTypController::class, 'edit'])
    ->name('jezykTyp.edit')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('jezykTyp/{jezykTyp}', [JezykTypController::class, 'update'])
    ->name('jezykTyp.update')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::delete('jezykTyp/{jezykTyp}', [JezykTypController::class, 'destroy'])
    ->name('jezykTyp.destroy')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('jezykTyp/{jezykTyp}/restore', [JezykTypController::class, 'restore'])
    ->name('jezykTyp.restore')
        ->middleware('auth', 'moze:slowniki.systemowe');


// Kraje

Route::get('krajTyp', [KrajTypController::class, 'index'])
    ->name('krajTyp')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('krajTyp/create', [KrajTypController::class, 'create'])
    ->name('krajTyp.create')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::post('krajTyp', [KrajTypController::class, 'store'])
    ->name('krajTyp.store')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::get('krajTyp/{krajTyp}/edit', [KrajTypController::class, 'edit'])
    ->name('krajTyp.edit')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('krajTyp/{krajTyp}', [KrajTypController::class, 'update'])
    ->name('krajTyp.update')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::delete('krajTyp/{krajTyp}', [KrajTypController::class, 'destroy'])
    ->name('krajTyp.destroy')
        ->middleware('auth', 'moze:slowniki.systemowe');


Route::put('krajTyp/{krajTyp}/restore', [KrajTypController::class, 'restore'])
    ->name('krajTyp.restore')
        ->middleware('auth', 'moze:slowniki.systemowe');


// ShiftStatus

Route::get('shiftStatusTyp', [ShiftStatusController::class, 'index'])
    ->name('shiftStatusTyp')
    ->middleware('auth', 'moze:slowniki.biura');

Route::get('shiftStatusTyp/create', [ShiftStatusController::class, 'create'])
    ->name('shiftStatusTyp.create')
    ->middleware('auth', 'moze:slowniki.biura');


Route::post('shiftStatusTyp', [ShiftStatusController::class, 'store'])
    ->name('shiftStatusTyp.store')
    ->middleware('auth', 'moze:slowniki.biura');


Route::get('shiftStatusTyp/{shiftStatus}/edit', [ShiftStatusController::class, 'edit'])
    ->name('shiftStatusTyp.edit')
    ->middleware('auth', 'moze:slowniki.biura');


Route::put('shiftStatusTyp/{shiftStatus}', [ShiftStatusController::class, 'update'])
    ->name('shiftStatusTyp.update')
    ->middleware('auth', 'moze:slowniki.biura');


Route::delete('shiftStatusTyp/{shiftStatus}', [ShiftStatusController::class, 'destroy'])
    ->name('shiftStatusTyp.destroy')
    ->middleware('auth', 'moze:slowniki.biura');


Route::put('shiftStatusTyp/{shiftStatus}/restore', [ShiftStatusController::class, 'restore'])
    ->name('shiftStatusTyp.restore')
    ->middleware('auth', 'moze:slowniki.biura');


// Reports

Route::get('reports', [ReportsController::class, 'index'])
    ->name('reports')
        ->middleware('auth', 'moze:kcp.raporty');


Route::get('reports/koniecUprawinien', [ReportsController::class, 'koniecUprawinien'])
    ->name('reports.koniecUprawinien')
        ->middleware('auth', 'moze:raport_terminow.podglad');


// Tools

// Rejestr logowań — tylko administrator
Route::get('logowania', [LogowaniaController::class, 'index'])
    ->name('logowania')
    ->middleware('auth', 'moze:rejestr_logowan.podglad');

Route::get('tools', [ToolsController::class, 'index'])
    ->name('tools')
    ->middleware('auth', 'moze:slowniki.biura');


// Zadania — proces testowania strony

Route::get('zadania', [ZadaniaController::class, 'index'])
    ->name('zadania.index')
    ->middleware('auth');

Route::get('zadania/create', [ZadaniaController::class, 'create'])
    ->name('zadania.create')
    ->middleware('auth');

Route::post('zadania', [ZadaniaController::class, 'store'])
    ->name('zadania.store')
    ->middleware('auth');

Route::get('zadania/{zadanie}', [ZadaniaController::class, 'show'])
    ->name('zadania.show')
    ->middleware('auth');

Route::get('zadania/{zadanie}/edit', [ZadaniaController::class, 'edit'])
    ->name('zadania.edit')
    ->middleware('auth');

Route::put('zadania/{zadanie}', [ZadaniaController::class, 'update'])
    ->name('zadania.update')
    ->middleware('auth');

Route::put('zadania/{zadanie}/status', [ZadaniaController::class, 'updateStatus'])
    ->name('zadania.status')
    ->middleware('auth');

Route::delete('zadania/{zadanie}', [ZadaniaController::class, 'destroy'])
    ->name('zadania.destroy')
    ->middleware('auth');

Route::put('zadania/{zadanie}/restore', [ZadaniaController::class, 'restore'])
    ->name('zadania.restore')
    ->middleware('auth');

Route::post('zadania/{zadanie}/files', [ZadaniaController::class, 'storeFiles'])
    ->name('zadania.files.store')
    ->middleware('auth');

Route::get('zadania/{zadanie}/files/{file}', [ZadaniaController::class, 'showFile'])
    ->name('zadania.files.show')
    ->middleware('auth');

Route::delete('zadania/{zadanie}/files/{file}', [ZadaniaController::class, 'destroyFile'])
    ->name('zadania.files.destroy')
    ->middleware('auth');


// Komentarze (dyskusja pod zgłoszeniem)

Route::post('notes', [NoteController::class, 'store'])
    ->name('notes.store')
    ->middleware('auth');

Route::put('notes/{note}', [NoteController::class, 'update'])
    ->name('notes.update')
    ->middleware('auth');

Route::delete('notes/{note}', [NoteController::class, 'destroy'])
    ->name('notes.destroy')
    ->middleware('auth');


// Powiadomienia

Route::post('notifications/{id}/read', [NotificationController::class, 'read'])
    ->name('notifications.read')
    ->middleware('auth');

Route::post('notifications/read-all', [NotificationController::class, 'readAll'])
    ->name('notifications.readAll')
    ->middleware('auth');


// Umowy i aneksy

Route::get('contacts/{contact}/umowa', [UmowyController::class, 'formularz'])
    ->name('umowy.formularz')
    ->middleware('auth', 'moze:kartoteki.edycja');

Route::get('contacts/{contact}/umowa/podglad', [UmowyController::class, 'podglad'])
    ->name('umowy.podglad')
    ->middleware('auth', 'moze:kartoteki.edycja');

Route::get('contacts/{contact}/umowa/doc', [UmowyController::class, 'doc'])
    ->name('umowy.doc')
    ->middleware('auth', 'moze:kartoteki.edycja');


// Zmiany kadrowe — skrzynka dla kadr (dział HR = uprawnienia biuro)

Route::get('zmiany-kadrowe', [ZmianyKadroweController::class, 'index'])
    ->name('zmiany-kadrowe')
    ->middleware('auth', 'moze:zmiany_kadrowe.obsluga');

Route::put('zmiany-kadrowe', [ZmianyKadroweController::class, 'update'])
    ->name('zmiany-kadrowe.update')
    ->middleware('auth', 'moze:zmiany_kadrowe.obsluga');


// Images
Route::get('/img/{path}', [ImagesController::class, 'show'])
    ->where('path', '.*')
    ->name('image');

// Dokumenty

Route::get('contacts/{contact_id}/documents/', [CtnDocumentsController::class, 'index'])
    ->name('documents.index')
        ->middleware('auth', 'moze:dokumenty.podglad');


Route::get('contacts/{contact_id}/documents/create', [CtnDocumentsController::class, 'create'])
    ->name('documents.create')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::post('contacts/{contact_id}/documents/store', [CtnDocumentsController::class, 'store'])
    ->name('documents.store')
        ->middleware('auth', 'moze:dokumenty.dodawanie');


Route::get('contacts/{contact_id}/documents/{document_id}', [CtnDocumentsController::class, 'view'])
    ->name('documents.view')
        ->middleware('auth', 'moze:dokumenty.podglad');


Route::put('contacts/{contact_id}/documents/{document_id}/restore', [CtnDocumentsController::class, 'restore'])
    ->name('documents.restore')
        ->middleware('auth', 'moze:dokumenty.usuwanie');

Route::delete('contacts/{contact_id}/documents/{document_id}', [CtnDocumentsController::class, 'delete'])
    ->name('documents.delete')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::delete('contacts/{contact_id}/documents/{document_id}/lekarskie', [CtnDocumentsController::class, 'deleteLek'])
    ->name('documentsLek.delete')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::delete('contacts/{contact_id}/documents/{document_id}/bhp', [CtnDocumentsController::class, 'deleteBhp'])
    ->name('documentsBhp.delete')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


Route::delete('contacts/{contact_id}/documents/{document_id}/uprawnienia', [CtnDocumentsController::class, 'deleteUpr'])
    ->name('documentsUpr.delete')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


// Ekran PBIOZ nie miał swojego wariantu i kasował skany trasą /bhp,
// przez co po usunięciu wyrzucało użytkownika na ekran szkoleń BHP.
Route::delete('contacts/{contact_id}/documents/{document_id}/pbioz', [CtnDocumentsController::class, 'deletePbioz'])
    ->name('documentsPbioz.delete')
        ->middleware('auth', 'moze:dokumenty.usuwanie');

Route::delete('contacts/{contact_id}/documents/{document_id}/a1', [CtnDocumentsController::class, 'deleteA1'])
    ->name('documentsA1.delete')
        ->middleware('auth', 'moze:dokumenty.usuwanie');


/** Building time sheets */
Route::get('building/{build}/time-sheet', [BuildingTimeSheet::class, 'view'])
    ->name('workTimeSheet.view')
    ->middleware('auth', 'moze:kcp.wpisywanie');

Route::post('building/{build}/time-sheet', [BuildingTimeSheet::class, 'store'])
    ->name('workTimeSheet.store')
    ->middleware('auth', 'moze:kcp.wpisywanie');

Route::post('building/{build}/time-sheet/delete', [BuildingTimeSheet::class, 'delete'])
    ->name('workTimeSheet.delete')
    ->middleware('auth', 'moze:kcp.wpisywanie');

Route::get('building/{build}/time-sheet/export', [BuildingTimeSheet::class, 'excelExport'])
    ->name('workTimeSheet.excelExport')
    ->middleware('auth', 'moze:kcp.wpisywanie');

Route::get('building/time-sheet/general-report', [BuildingTimeSheet::class, 'buildsReport'])
    ->name('workTimeSheet.buildsReport')
    ->middleware('auth', 'moze:kcp.raporty');

Route::get('building/time-sheet/month-report', [BuildingTimeSheet::class, 'reportIndex'])
    ->name('workTimeSheet.reportIndex')
    ->middleware('auth', 'moze:kcp.raporty');

/** Country Feasts */
// Kalendarz dni wolnych to slownik jak Kraj — wchodzi sie tu wylacznie
// z edycji kraju, a ta jest admin-only. Trasom brakowalo tego sprawdzenia,
// wiec kazdy zalogowany (takze kierownik budowy) mogl dodawac i kasowac
// swieta, a te wplywaja na rozliczenie godzin.
Route::get('country/{country}/feasts', [FeastsController::class, 'index'])
    ->name('country_feasts.index')
    ->middleware('auth', 'moze:slowniki.systemowe');

Route::get('country/{country}/feasts/create', [FeastsController::class, 'create'])
    ->name('country_feasts.create')
    ->middleware('auth', 'moze:slowniki.systemowe');

Route::get('country/{country}/feasts/{feast}', [FeastsController::class, 'edit'])
    ->name('country_feasts.edit')
    ->middleware('auth', 'moze:slowniki.systemowe');

Route::post('country/{country}/feasts', [FeastsController::class, 'store'])
    ->name('country_feasts.store')
    ->middleware('auth', 'moze:slowniki.systemowe');

Route::delete('country/{country}/feasts/{feast}/delete', [FeastsController::class, 'delete'])
    ->name('country_feasts.delete')
    ->middleware('auth', 'moze:slowniki.systemowe');


// Baza wiedzy — czyta każdy zalogowany, pisze administrator.
// "create" przed "{artykul}", inaczej Laravel wziąłby je za identyfikator.
Route::get('baza-wiedzy', [BazaWiedzyController::class, 'index'])
    ->name('bazaWiedzy')
        ->middleware('auth');

Route::get('baza-wiedzy/create', [BazaWiedzyController::class, 'create'])
    ->name('bazaWiedzy.create')
        ->middleware('auth', 'moze:baza_wiedzy.pisanie');

Route::post('baza-wiedzy', [BazaWiedzyController::class, 'store'])
    ->name('bazaWiedzy.store')
        ->middleware('auth', 'moze:baza_wiedzy.pisanie');

Route::get('baza-wiedzy/{artykul}', [BazaWiedzyController::class, 'show'])
    ->name('bazaWiedzy.show')
        ->middleware('auth');

Route::get('baza-wiedzy/{artykul}/edit', [BazaWiedzyController::class, 'edit'])
    ->name('bazaWiedzy.edit')
        ->middleware('auth', 'moze:baza_wiedzy.pisanie');

Route::put('baza-wiedzy/{artykul}', [BazaWiedzyController::class, 'update'])
    ->name('bazaWiedzy.update')
        ->middleware('auth', 'moze:baza_wiedzy.pisanie');

Route::delete('baza-wiedzy/{artykul}', [BazaWiedzyController::class, 'destroy'])
    ->name('bazaWiedzy.destroy')
        ->middleware('auth', 'moze:baza_wiedzy.pisanie');


// Statystyki budów — podsumowanie Karty Czasu Pracy.
Route::get('statystyki', [StatystykiController::class, 'index'])
    ->name('statystyki')
        ->middleware('auth', 'moze:statystyki.podglad');

// Grupy sprzętu — słownik jak pozostałe w Ustawieniach.
Route::get('grupy-sprzetu', [GrupySprzetuController::class, 'index'])
    ->name('grupySprzetu')
        ->middleware('auth', 'moze:slowniki.biura');

Route::post('grupy-sprzetu', [GrupySprzetuController::class, 'store'])
    ->name('grupySprzetu.store')
        ->middleware('auth', 'moze:slowniki.biura');

Route::put('grupy-sprzetu/{grupa}', [GrupySprzetuController::class, 'update'])
    ->name('grupySprzetu.update')
        ->middleware('auth', 'moze:slowniki.biura');

Route::delete('grupy-sprzetu/{grupa}', [GrupySprzetuController::class, 'destroy'])
    ->name('grupySprzetu.destroy')
        ->middleware('auth', 'moze:slowniki.biura');

Route::post('grupy-sprzetu/przypisz', [GrupySprzetuController::class, 'przypisz'])
    ->name('grupySprzetu.przypisz')
        ->middleware('auth', 'moze:slowniki.biura');
