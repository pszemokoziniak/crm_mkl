<?php

use App\Http\Controllers\A1Controller;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
//use App\Http\Controllers\BadaniaBHPController;
use App\Http\Controllers\BadaniaController;
use App\Http\Controllers\BadaniaTypController;
use App\Http\Controllers\BhpController;
use App\Http\Controllers\BhpTypController;
use App\Http\Controllers\BudowaPracownicyController;
use App\Http\Controllers\BuildingTimeSheet;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\CountryFeastsController;
use App\Http\Controllers\CtnDocumentsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumentyTypController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\JezykController;
use App\Http\Controllers\JezykTypController;
use App\Http\Controllers\KlientController;
use App\Http\Controllers\KrajTypController;
use App\Http\Controllers\NarzedziaController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\PbiozController;
use App\Http\Controllers\ReportsController;
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

// Dashboard

Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');

// Users

Route::get('users', [UsersController::class, 'index'])
    ->name('users')
    ->middleware('auth');

Route::get('users/create', [UsersController::class, 'create'])
    ->name('users.create')
    ->middleware('auth');

Route::post('users', [UsersController::class, 'store'])
    ->name('users.store')
    ->middleware('auth');

Route::get('users/{user}/edit', [UsersController::class, 'edit'])
    ->name('users.edit')
    ->middleware('auth');

Route::put('users/{user}', [UsersController::class, 'update'])
    ->name('users.update')
    ->middleware('auth');

Route::delete('users/{user}', [UsersController::class, 'destroy'])
    ->name('users.destroy')
    ->middleware('auth');

Route::put('users/{user}/restore', [UsersController::class, 'restore'])
    ->name('users.restore')
    ->middleware('auth');

// Organizations

Route::get('budowy', [OrganizationsController::class, 'index'])
    ->name('organizations')
    ->middleware('auth');

Route::get('budowy/create', [OrganizationsController::class, 'create'])
    ->name('organizations.create')
    ->middleware('auth');

Route::post('budowy', [OrganizationsController::class, 'store'])
    ->name('organizations.store')
    ->middleware('auth');

Route::get('budowy/{organization}/edit', [OrganizationsController::class, 'edit'])
    ->name('organizations.edit')
    ->middleware('auth');

Route::put('budowy/{organization}', [OrganizationsController::class, 'update'])
    ->name('organizations.update')
    ->middleware('auth');

Route::delete('budowy/{organization}', [OrganizationsController::class, 'destroy'])
    ->name('organizations.destroy')
    ->middleware('auth');

Route::put('budowy/{organization}/restore', [OrganizationsController::class, 'restore'])
    ->name('organizations.restore')
    ->middleware('auth');

// Klient Budowa

Route::get('budowy/{organization}/klient', [KlientController::class, 'index'])
    ->name('klient.index')
    ->middleware('auth');

Route::get('budowy/{organization}/klient/create', [KlientController::class, 'create'])
    ->name('klient.create')
    ->middleware('auth');

Route::post('klient/', [KlientController::class, 'store'])
    ->name('klient.store')
    ->middleware('auth');

Route::get('budowy/{organization}/klient/{klient}/edit', [KlientController::class, 'edit'])
    ->name('klient.edit')
    ->middleware('auth');

Route::put('klient/{klient}', [KlientController::class, 'update'])
    ->name('klient.update')
    ->middleware('auth');

Route::delete('klient/{klient}/delete', [KlientController::class, 'destroy'])
    ->name('klient.destroy')
    ->middleware('auth');

Route::put('budowy/{klient}/restore', [KlientController::class, 'restore'])
    ->name('klient.restore')
    ->middleware('auth');

// Contacts

Route::get('contacts', [ContactsController::class, 'index'])
    ->name('contacts')
    ->middleware('auth');

Route::get('contacts/create', [ContactsController::class, 'create'])
    ->name('contacts.create')
    ->middleware('auth');

Route::post('contacts', [ContactsController::class, 'store'])
    ->name('contacts.store')
    ->middleware('auth');

Route::get('contacts/{contact}/edit', [ContactsController::class, 'edit'])
    ->name('contacts.edit')
    ->middleware('auth');

Route::put('contacts/{contact}', [ContactsController::class, 'update'])
    ->name('contacts.update')
    ->middleware('auth');

Route::delete('contacts/{contact}', [ContactsController::class, 'destroy'])
    ->name('contacts.destroy')
    ->middleware('auth');

Route::put('contacts/{contact}/restore', [ContactsController::class, 'restore'])
    ->name('contacts.restore')
    ->middleware('auth');

// Narzedzia

Route::get('narzedzia', [NarzedziaController::class, 'index'])
    ->name('narzedzia')
    ->middleware('auth');

Route::get('narzedzia/create', [NarzedziaController::class, 'create'])
    ->name('narzedzia.create')
    ->middleware('auth');

Route::post('narzedzia', [NarzedziaController::class, 'store'])
    ->name('narzedzia.store')
    ->middleware('auth');

Route::get('narzedzia/{narzedzia}/edit', [NarzedziaController::class, 'edit'])
    ->name('narzedzia.edit')
    ->middleware('auth');

Route::put('narzedzia/{narzedzia}', [NarzedziaController::class, 'update'])
    ->name('narzedzia.update')
    ->middleware('auth');

Route::delete('narzedzia/{narzedzia}', [NarzedziaController::class, 'destroy'])
    ->name('narzedzia.destroy')
    ->middleware('auth');

Route::put('narzedzia/{narzedzia}/restore', [NarzedziaController::class, 'restore'])
    ->name('narzedzia.restore')
    ->middleware('auth');

// Narzedzia Budowa

Route::get('narzedzia', [NarzedziaController::class, 'index'])
    ->name('narzedzia')
    ->middleware('auth');

Route::get('narzedzia/create', [NarzedziaController::class, 'create'])
    ->name('narzedzia.create')
    ->middleware('auth');

Route::post('narzedzia', [NarzedziaController::class, 'store'])
    ->name('narzedzia.store')
    ->middleware('auth');

Route::get('narzedzia/{narzedzia}/edit', [NarzedziaController::class, 'edit'])
    ->name('narzedzia.edit')
    ->middleware('auth');

Route::put('narzedzia/{narzedzia}', [NarzedziaController::class, 'update'])
    ->name('narzedzia.update')
    ->middleware('auth');

Route::delete('narzedzia/{narzedzia}', [NarzedziaController::class, 'destroy'])
    ->name('narzedzia.destroy')
    ->middleware('auth');

Route::put('narzedzia/{narzedzia}/restore', [NarzedziaController::class, 'restore'])
    ->name('narzedzia.restore')
    ->middleware('auth');

// Dokumenty Typ

Route::get('dokumentyTyp', [DokumentyTypController::class, 'index'])
    ->name('dokumentyTyp')
    ->middleware('auth');

Route::get('dokumentyTyp/create', [DokumentyTypController::class, 'create'])
    ->name('dokumentyTyp.create')
    ->middleware('auth');

Route::post('dokumentyTyp', [DokumentyTypController::class, 'store'])
    ->name('dokumentyTyp.store')
    ->middleware('auth');

Route::get('dokumentyTyp/{dokumentyTyp}/edit', [DokumentyTypController::class, 'edit'])
    ->name('dokumentyTyp.edit')
    ->middleware('auth');

Route::put('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'update'])
    ->name('dokumentyTyp.update')
    ->middleware('auth');

Route::delete('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'destroy'])
    ->name('dokumentyTyp.destroy')
    ->middleware('auth');

Route::put('dokumentyTyp/{account}/restore', [DokumentyTypController::class, 'restore'])
    ->name('dokumentyTyp.restore')
    ->middleware('auth');

// Dodawanie pracownicy budowa

Route::get('pracownicy/{organization}', [BudowaPracownicyController::class, 'index'])
    ->name('pracownicy.index')
    ->middleware('auth');

Route::post('pracownicy/{organization}/create', [BudowaPracownicyController::class, 'find'])
    ->name('pracownicy.create.post')
    ->middleware('auth');

Route::get('pracownicy/{organization}/create', [BudowaPracownicyController::class, 'create'])
    ->name('pracownicy.create')
    ->middleware('auth');

Route::get('pracownicy/{organization}/edit/{contact}', [BudowaPracownicyController::class, 'edit'])
    ->name('pracownicy.edit')
    ->middleware('auth');

Route::post('pracownicy/{organization}', [BudowaPracownicyController::class, 'store'])
    ->name('pracownicy.store')
    ->middleware('auth');

//Route::post('api/pracownicy/{organization}/find', [BudowaPracownicyController::class, 'find'])
//    ->name('pracownicy.find')
//    ->middleware('auth');

Route::get('pracownicy/{organization}/destroy/{contact}', [BudowaPracownicyController::class, 'destroy'])
    ->name('pracownicy.destroy')
    ->middleware('auth');

Route::put('pracownicy/destroystore', [BudowaPracownicyController::class, 'destroyStore'])
    ->name('pracownicy.destroystore')
    ->middleware('auth');

// Destroy pracownicy budowa
Route::get('contacts/{contact}/budowa/destroy', [ContactsController::class, 'destroyPracownikBudowa'])
    ->name('contacts.destroyPracownikBudowa')
    ->middleware('auth');

// Badania

Route::get('contacts/{contact}/badania', [BadaniaController::class, 'index'])
    ->name('badania.index')
    ->middleware('auth');

Route::get('contacts/{contact}/badania/create', [BadaniaController::class, 'create'])
    ->name('badania.create')
    ->middleware('auth');

Route::post('badania/{contact_id}', [BadaniaController::class, 'store'])
    ->name('badania.store')
    ->middleware('auth');

Route::get('contacts/{contact}/badania/{badania}/edit', [BadaniaController::class, 'edit'])
    ->name('badania.edit')
    ->middleware('auth');

Route::put('contacts/{contact}/badania/{badania}', [BadaniaController::class, 'update'])
    ->name('badania.update')
    ->middleware('auth');

Route::delete('badania/{badania}', [BadaniaController::class, 'destroy'])
    ->name('badania.destroy')
    ->middleware('auth');

Route::put('badania/{badania}/restore', [BadaniaController::class, 'restore'])
    ->name('badania.restore')
    ->middleware('auth');

// BHP

Route::get('contacts/{contact}/bhp', [BhpController::class, 'index'])
    ->name('bhp.index')
    ->middleware('auth');

Route::get('contacts/{contact}/bhp/create', [BhpController::class, 'create'])
    ->name('bhp.create')
    ->middleware('auth');

Route::post('bhp/{contact_id}', [BhpController::class, 'store'])
    ->name('bhp.store')
    ->middleware('auth');

Route::get('contacts/{contact}/bhp/{bhp}/edit', [BhpController::class, 'edit'])
    ->name('bhp.edit')
    ->middleware('auth');

Route::put('contacts/{contact}/bhp/{bhp}', [BhpController::class, 'update'])
    ->name('bhp.update')
    ->middleware('auth');

Route::delete('bhp/{bhp}', [BhpController::class, 'destroy'])
    ->name('bhp.destroy')
    ->middleware('auth');

Route::put('bhp/{bhp}/restore', [BhpController::class, 'restore'])
    ->name('bhp.restore')
    ->middleware('auth');

// Pbioz

Route::get('contacts/{contact}/pbioz', [PbiozController::class, 'index'])
    ->name('pbioz.index')
    ->middleware('auth');

Route::get('contacts/{contact}/pbioz/create', [PbiozController::class, 'create'])
    ->name('pbioz.create')
    ->middleware('auth');

Route::post('pbioz/{contact_id}', [PbiozController::class, 'store'])
    ->name('pbioz.store')
    ->middleware('auth');

Route::get('contacts/{contact}/pbioz/{pbioz}/edit', [PbiozController::class, 'edit'])
    ->name('pbioz.edit')
    ->middleware('auth');

Route::put('contacts/{contact}/pbioz/{pbioz}', [PbiozController::class, 'update'])
    ->name('pbioz.update')
    ->middleware('auth');

Route::delete('pbioz/{pbioz}', [PbiozController::class, 'destroy'])
    ->name('pbioz.destroy')
    ->middleware('auth');

Route::put('pbioz/{pbioz}/restore', [PbiozController::class, 'restore'])
    ->name('pbioz.restore')
    ->middleware('auth');

// A1

Route::get('contacts/{contact}/a1', [A1Controller::class, 'index'])
    ->name('a1.index')
    ->middleware('auth');

Route::get('contacts/{contact}/a1/create', [A1Controller::class, 'create'])
    ->name('a1.create')
    ->middleware('auth');

Route::post('a1/{contact_id}', [A1Controller::class, 'store'])
    ->name('a1.store')
    ->middleware('auth');

Route::get('contacts/{contact}/a1/{a1}/edit', [A1Controller::class, 'edit'])
    ->name('a1.edit')
    ->middleware('auth');

Route::put('contacts/{contact}/a1/{a1}', [A1Controller::class, 'update'])
    ->name('a1.update')
    ->middleware('auth');

Route::delete('a1/{a1}', [A1Controller::class, 'destroy'])
    ->name('a1.destroy')
    ->middleware('auth');

Route::put('a1/{a1}/restore', [A1Controller::class, 'restore'])
    ->name('a1.restore')
    ->middleware('auth');

// Uprawnienia

Route::get('contacts/{contact}/uprawnienia', [UprawnieniaController::class, 'index'])
    ->name('uprawnienia.index')
    ->middleware('auth');

Route::get('contacts/{contact}/uprawnienia/create', [UprawnieniaController::class, 'create'])
    ->name('uprawnienia.create')
    ->middleware('auth');

Route::post('uprawnienia/{contact_id}', [UprawnieniaController::class, 'store'])
    ->name('uprawnienia.store')
    ->middleware('auth');

Route::get('contacts/{contact}/uprawnienia/{uprawnienia}/edit', [UprawnieniaController::class, 'edit'])
    ->name('uprawnienia.edit')
    ->middleware('auth');

Route::put('contacts/{contact}/uprawnienia/{uprawnienia}', [UprawnieniaController::class, 'update'])
    ->name('uprawnienia.update')
    ->middleware('auth');

Route::delete('uprawnienia/{uprawnienia}', [UprawnieniaController::class, 'destroy'])
    ->name('uprawnienia.destroy')
    ->middleware('auth');

Route::put('uprawnienia/{uprawnienia}/restore', [UprawnieniaController::class, 'restore'])
    ->name('uprawnienia.restore')
    ->middleware('auth');

// Języki

Route::get('contacts/{contact}/jezyk', [JezykController::class, 'index'])
    ->name('jezyk.index')
    ->middleware('auth');

Route::get('contacts/{contact}/jezyk/create', [JezykController::class, 'create'])
    ->name('jezyk.create')
    ->middleware('auth');

Route::post('jezyk/{contact_id}', [JezykController::class, 'store'])
    ->name('jezyk.store')
    ->middleware('auth');

Route::get('contacts/{contact}/jezyk/{jezyk}/edit', [JezykController::class, 'edit'])
    ->name('jezyk.edit')
    ->middleware('auth');

Route::put('contacts/{contact}/jezyk/{jezyk}', [JezykController::class, 'update'])
    ->name('jezyk.update')
    ->middleware('auth');

Route::delete('jezyk/{jezyk}', [JezykController::class, 'destroy'])
    ->name('jezyk.destroy')
    ->middleware('auth');

Route::put('jezyk/{jezyk}/restore', [JezykController::class, 'restore'])
    ->name('jezyk.restore')
    ->middleware('auth');

// Accounts

Route::get('position', [AccountsController::class, 'index'])
    ->name('position')
    ->middleware('auth');

Route::get('position/create', [AccountsController::class, 'create'])
    ->name('position.create')
    ->middleware('auth');

Route::post('position', [AccountsController::class, 'store'])
    ->name('position.store')
    ->middleware('auth');

Route::get('position/{account}/edit', [AccountsController::class, 'edit'])
    ->name('position.edit')
    ->middleware('auth');

Route::put('position/{account}', [AccountsController::class, 'update'])
    ->name('position.update')
    ->middleware('auth');

Route::delete('position/{account}', [AccountsController::class, 'destroy'])
    ->name('position.destroy')
    ->middleware('auth');

Route::put('position/{account}/restore', [AccountsController::class, 'restore'])
    ->name('position.restore')
    ->middleware('auth');


// Funkcja Typ

Route::get('funkcja', [FunkcjaController::class, 'index'])
    ->name('funkcja')
    ->middleware('auth');

Route::get('funkcja/create', [FunkcjaController::class, 'create'])
    ->name('funkcja.create')
    ->middleware('auth');

Route::post('funkcja', [FunkcjaController::class, 'store'])
    ->name('funkcja.store')
    ->middleware('auth');

Route::get('funkcja/{funkcja}/edit', [FunkcjaController::class, 'edit'])
    ->name('funkcja.edit')
    ->middleware('auth');

Route::put('funkcja/{funkcja}', [FunkcjaController::class, 'update'])
    ->name('funkcja.update')
    ->middleware('auth');

Route::delete('funkcja/{funkcja}', [FunkcjaController::class, 'destroy'])
    ->name('funkcja.destroy')
    ->middleware('auth');

Route::put('funkcja/{account}/restore', [FunkcjaController::class, 'restore'])
    ->name('funkcja.restore')
    ->middleware('auth');

// Dokumenty Typ

Route::get('dokumentyTyp', [DokumentyTypController::class, 'index'])
    ->name('dokumentyTyp')
    ->middleware('auth');

Route::get('dokumentyTyp/create', [DokumentyTypController::class, 'create'])
    ->name('dokumentyTyp.create')
    ->middleware('auth');

Route::post('dokumentyTyp', [DokumentyTypController::class, 'store'])
    ->name('dokumentyTyp.store')
    ->middleware('auth');

Route::get('dokumentyTyp/{dokumentyTyp}/edit', [DokumentyTypController::class, 'edit'])
    ->name('dokumentyTyp.edit')
    ->middleware('auth');

Route::put('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'update'])
    ->name('dokumentyTyp.update')
    ->middleware('auth');

Route::delete('dokumentyTyp/{dokumentyTyp}', [DokumentyTypController::class, 'destroy'])
    ->name('dokumentyTyp.destroy')
    ->middleware('auth');

Route::put('dokumentyTyp/{account}/restore', [DokumentyTypController::class, 'restore'])
    ->name('dokumentyTyp.restore')
    ->middleware('auth');

// Badania Typ

Route::get('badaniaTyp', [BadaniaTypController::class, 'index'])
    ->name('badaniaTyp')
    ->middleware('auth');

Route::get('badaniaTyp/create', [BadaniaTypController::class, 'create'])
    ->name('badaniaTyp.create')
    ->middleware('auth');

Route::post('badaniaTyp', [BadaniaTypController::class, 'store'])
    ->name('badaniaTyp.store')
    ->middleware('auth');

Route::get('badaniaTyp/{badaniaTyp}/edit', [BadaniaTypController::class, 'edit'])
    ->name('badaniaTyp.edit')
    ->middleware('auth');

Route::put('badaniaTyp/{badaniaTyp}', [BadaniaTypController::class, 'update'])
    ->name('badaniaTyp.update')
    ->middleware('auth');

Route::delete('badaniaTyp/{badaniaTyp}', [BadaniaTypController::class, 'destroy'])
    ->name('badaniaTyp.destroy')
    ->middleware('auth');

Route::put('badaniaTyp/{badaniaTyp}/restore', [BadaniaTypController::class, 'restore'])
    ->name('badaniaTyp.restore')
    ->middleware('auth');

// BHP Typ

Route::get('bhpTyp', [BhpTypController::class, 'index'])
    ->name('bhpTyp')
    ->middleware('auth');

Route::get('bhpTyp/create', [BhpTypController::class, 'create'])
    ->name('bhpTyp.create')
    ->middleware('auth');

Route::post('bhpTyp', [BhpTypController::class, 'store'])
    ->name('bhpTyp.store')
    ->middleware('auth');

Route::get('bhpTyp/{bhpTyp}/edit', [BhpTypController::class, 'edit'])
    ->name('bhpTyp.edit')
    ->middleware('auth');

Route::put('bhpTyp/{bhpTyp}', [BhpTypController::class, 'update'])
    ->name('bhpTyp.update')
    ->middleware('auth');

Route::delete('bhpTyp/{bhpTyp}', [BhpTypController::class, 'destroy'])
    ->name('bhpTyp.destroy')
    ->middleware('auth');

Route::put('bhpTyp/{bhpTyp}/restore', [BhpTypController::class, 'restore'])
    ->name('bhpTyp.restore')
    ->middleware('auth');

// Uprawnienia Typ

Route::get('uprawnieniaTyp', [UprawnieniaTypController::class, 'index'])
    ->name('uprawnieniaTyp')
    ->middleware('auth');

Route::get('uprawnieniaTyp/create', [UprawnieniaTypController::class, 'create'])
    ->name('uprawnieniaTyp.create')
    ->middleware('auth');

Route::post('uprawnieniaTyp', [UprawnieniaTypController::class, 'store'])
    ->name('uprawnieniaTyp.store')
    ->middleware('auth');

Route::get('uprawnieniaTyp/{uprawnieniaTyp}/edit', [UprawnieniaTypController::class, 'edit'])
    ->name('uprawnieniaTyp.edit')
    ->middleware('auth');

Route::put('uprawnieniaTyp/{uprawnieniaTyp}', [UprawnieniaTypController::class, 'update'])
    ->name('uprawnieniaTyp.update')
    ->middleware('auth');

Route::delete('uprawnieniaTyp/{uprawnieniaTyp}', [UprawnieniaTypController::class, 'destroy'])
    ->name('uprawnieniaTyp.destroy')
    ->middleware('auth');

Route::put('uprawnieniaTyp/{uprawnieniaTyp}/restore', [UprawnieniaTypController::class, 'restore'])
    ->name('uprawnieniaTyp.restore')
    ->middleware('auth');

// Języki

Route::get('jezykTyp', [JezykTypController::class, 'index'])
    ->name('jezykTyp')
    ->middleware('auth');

Route::get('jezykTyp/create', [JezykTypController::class, 'create'])
    ->name('jezykTyp.create')
    ->middleware('auth');

Route::post('jezykTyp', [JezykTypController::class, 'store'])
    ->name('jezykTyp.store')
    ->middleware('auth');

Route::get('jezykTyp/{jezykTyp}/edit', [JezykTypController::class, 'edit'])
    ->name('jezykTyp.edit')
    ->middleware('auth');

Route::put('jezykTyp/{jezykTyp}', [JezykTypController::class, 'update'])
    ->name('jezykTyp.update')
    ->middleware('auth');

Route::delete('jezykTyp/{jezykTyp}', [JezykTypController::class, 'destroy'])
    ->name('jezykTyp.destroy')
    ->middleware('auth');

Route::put('jezykTyp/{jezykTyp}/restore', [JezykTypController::class, 'restore'])
    ->name('jezykTyp.restore')
    ->middleware('auth');

// Kraje

Route::get('krajTyp', [KrajTypController::class, 'index'])
    ->name('krajTyp')
    ->middleware('auth');

Route::get('krajTyp/create', [KrajTypController::class, 'create'])
    ->name('krajTyp.create')
    ->middleware('auth');

Route::post('krajTyp', [KrajTypController::class, 'store'])
    ->name('krajTyp.store')
    ->middleware('auth');

Route::get('krajTyp/{krajTyp}/edit', [KrajTypController::class, 'edit'])
    ->name('krajTyp.edit')
    ->middleware('auth');

Route::put('krajTyp/{krajTyp}', [KrajTypController::class, 'update'])
    ->name('krajTyp.update')
    ->middleware('auth');

Route::delete('krajTyp/{krajTyp}', [KrajTypController::class, 'destroy'])
    ->name('krajTyp.destroy')
    ->middleware('auth');

Route::put('krajTyp/{krajTyp}/restore', [KrajTypController::class, 'restore'])
    ->name('krajTyp.restore')
    ->middleware('auth');

// Reports

Route::get('reports', [ReportsController::class, 'index'])
    ->name('reports')
    ->middleware('auth');

Route::get('reports/koniecUprawinien', [ReportsController::class, 'koniecUprawinien'])
    ->name('reports.koniecUprawinien')
    ->middleware('auth');

// Tools

Route::get('tools', [ToolsController::class, 'index'])
->name('tools')
->middleware('auth');

// Images
Route::get('/img/{path}', [ImagesController::class, 'show'])
    ->where('path', '.*')
    ->name('image');

// Dokumenty

Route::get('contacts/{contact_id}/documents/', [CtnDocumentsController::class, 'index'])
    ->name('documents.index')
    ->middleware('auth');

Route::get('contacts/{contact_id}/documents/create', [CtnDocumentsController::class, 'create'])
    ->name('documents.create')
    ->middleware('auth');

Route::post('contacts/{contact_id}/documents/store', [CtnDocumentsController::class, 'store'])
    ->name('documents.store')
    ->middleware('auth');

Route::get('contacts/{contact_id}/documents/{document_id}', [CtnDocumentsController::class, 'view'])
    ->name('documents.view')
    ->middleware('auth');

Route::delete('contacts/{contact_id}/documents/{document_id}', [CtnDocumentsController::class, 'delete'])
    ->name('documents.delete')
    ->middleware('auth');

Route::delete('contacts/{contact_id}/documents/{document_id}/lekarskie', [CtnDocumentsController::class, 'deleteLek'])
    ->name('documentsLek.delete')
    ->middleware('auth');

Route::delete('contacts/{contact_id}/documents/{document_id}/bhp', [CtnDocumentsController::class, 'deleteBhp'])
    ->name('documentsBhp.delete')
    ->middleware('auth');

Route::delete('contacts/{contact_id}/documents/{document_id}/uprawnienia', [CtnDocumentsController::class, 'deleteUpr'])
    ->name('documentsUpr.delete')
    ->middleware('auth');

Route::delete('contacts/{contact_id}/documents/{document_id}/a1', [CtnDocumentsController::class, 'deleteA1'])
    ->name('documentsA1.delete')
    ->middleware('auth');

/** Building time sheets */
Route::get('building/{build}/time-sheet', [BuildingTimeSheet::class, 'view'])
    ->name('workTimeSheet.view')
    ->middleware('auth');

Route::post('building/{build}/time-sheet', [BuildingTimeSheet::class, 'store'])
    ->name('workTimeSheet.store')
    ->middleware('auth');

/** Country Feasts */
Route::get('country/{country}/feasts', [CountryFeastsController::class, 'index'])
    ->name('country_feasts.index')
    ->middleware('auth');

Route::get('country/{country}/feasts/create', [CountryFeastsController::class, 'create'])
    ->name('country_feasts.create')
    ->middleware('auth');

Route::get('country/{country}/feasts/{feast}', [CountryFeastsController::class, 'edit'])
    ->name('country_feasts.edit')
    ->middleware('auth');

Route::post('country/{country}/feasts', [CountryFeastsController::class, 'store'])
    ->name('country_feasts.store')
    ->middleware('auth');
