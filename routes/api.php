<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Client\ClientUserController;
use App\Http\Controllers\Staff\EventController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Staff\AccountRoleController;
use App\Http\Controllers\Staff\StaffUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
*/

// Admin Login
Route::post('/admin/login', [AdminUserController::class, 'login'])
    ->name('admin.login');

// Staff Login
Route::post('/staff/login', [StaffUserController::class, 'login'])
    ->name('staff.login');

// Client Login
Route::post('/clients/login', [ClientUserController::class, 'login'])
    ->name('clients.login');

/*
|--------------------------------------------------------------------------
| Protected Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:admin')->prefix('admin')->group(function () {
    Route::get('/profile', [AdminUserController::class, 'profile'])
        ->name('admin.profile');

    Route::put('/profile', [AdminUserController::class, 'updateProfile'])
        ->name('admin.update_profile');

    Route::put('/password', [AdminUserController::class, 'changePassword'])
        ->name('admin.change_password');

    // Account Management
    Route::get('/accounts', [AccountController::class, 'index'])
        ->name('admin.accounts.index');

    Route::post('/accounts', [AccountController::class, 'store'])
        ->name('admin.accounts.store');

    Route::put('/accounts/{id}', [AccountController::class, 'update'])
        ->name('admin.accounts.update');
});

/*
|--------------------------------------------------------------------------
| Protected Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:staff')->prefix('staff')->group(function () {
    // Profile Management
    Route::get('/profile', [StaffUserController::class, 'profile'])
        ->name('staff.profile');

    Route::put('/profile', [StaffUserController::class, 'updateProfile'])
        ->name('staff.update_profile');

    Route::put('/password', [StaffUserController::class, 'changePassword'])
        ->name('staff.change_password');

    // Role Management
    Route::get('/roles', [AccountRoleController::class, 'index'])
        ->name('staff.roles.index');

    Route::post('/roles', [AccountRoleController::class, 'store'])
        ->name('staff.roles.store');

    Route::put('/roles/{id}', [AccountRoleController::class, 'update'])
        ->name('staff.roles.update');

    Route::delete('/roles/{id}', [AccountRoleController::class, 'destroy'])
        ->name('staff.roles.destroy');

    // Staff Management
    Route::get('/staff', [StaffController::class, 'index'])
        ->name('staff.staff.index');

    Route::post('/staff', [StaffController::class, 'store'])
        ->name('staff.staff.store');

    Route::put('/staff/{id}', [StaffController::class, 'update'])
        ->name('staff.staff.update');

    Route::delete('/staff/{id}', [StaffController::class, 'destroy'])
        ->name('staff.staff.destroy');

    // Event Management
    Route::get('/events', [EventController::class, 'index'])
        ->name('staff.events.index');

    Route::post('/events', [EventController::class, 'store'])
        ->name('staff.events.store');

    Route::put('/events/{id}', [EventController::class, 'update'])
        ->name('staff.events.update');
});

/*
|--------------------------------------------------------------------------
| Protected Client Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:client')->prefix('clients')->group(function () {
    Route::get('/profile', [ClientUserController::class, 'profile'])
        ->name('clients.profile');
    
    Route::put('/profile', [ClientUserController::class, 'updateProfile'])
        ->name('clients.update_profile');
    
    Route::put('/password', [ClientUserController::class, 'changePassword'])
        ->name('clients.change_password');
});
