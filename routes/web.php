<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BadaniaBHPController;
use App\Http\Controllers\BadaniaController;
use App\Http\Controllers\BadaniaTypController;
use App\Http\Controllers\BhpController;
use App\Http\Controllers\BhpTypController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\CtnDocumentsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\JezykController;
use App\Http\Controllers\JezykTypController;
use App\Http\Controllers\KlientController;
use App\Http\Controllers\KrajTypController;
use App\Http\Controllers\OrganizationsController;
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


// Funkcja

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
