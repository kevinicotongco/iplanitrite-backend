<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Client\ClientUserController;
use App\Http\Controllers\SupplierStaff\EventController;
use App\Http\Controllers\SupplierStaff\SupplierStaffController;
use App\Http\Controllers\SupplierStaff\SupplierStaffRoleController;
use App\Http\Controllers\SupplierStaff\SupplierStaffUserController;
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

// Supplier Staff Login
Route::post('/supplier-staff/login', [SupplierStaffUserController::class, 'login'])
    ->name('supplier_staff.login');

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

    // Supplier Management
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->name('admin.suppliers.index');

    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->name('admin.suppliers.store');

    Route::put('/suppliers/{id}', [SupplierController::class, 'update'])
        ->name('admin.suppliers.update');
});

/*
|--------------------------------------------------------------------------
| Protected Supplier Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:supplier_staff')->prefix('supplier-staff')->group(function () {
    // Profile Management
    Route::get('/profile', [SupplierStaffUserController::class, 'profile'])
        ->name('supplier_staff.profile');

    Route::put('/profile', [SupplierStaffUserController::class, 'updateProfile'])
        ->name('supplier_staff.update_profile');

    Route::put('/password', [SupplierStaffUserController::class, 'changePassword'])
        ->name('supplier_staff.change_password');

    // Role Management
    Route::get('/roles', [SupplierStaffRoleController::class, 'index'])
        ->name('supplier_staff.roles.index');

    Route::post('/roles', [SupplierStaffRoleController::class, 'store'])
        ->name('supplier_staff.roles.store');

    Route::put('/roles/{id}', [SupplierStaffRoleController::class, 'update'])
        ->name('supplier_staff.roles.update');

    Route::delete('/roles/{id}', [SupplierStaffRoleController::class, 'destroy'])
        ->name('supplier_staff.roles.destroy');

    // Staff Management
    Route::get('/staff', [SupplierStaffController::class, 'index'])
        ->name('supplier_staff.staff.index');

    Route::post('/staff', [SupplierStaffController::class, 'store'])
        ->name('supplier_staff.staff.store');

    Route::put('/staff/{id}', [SupplierStaffController::class, 'update'])
        ->name('supplier_staff.staff.update');

    Route::delete('/staff/{id}', [SupplierStaffController::class, 'destroy'])
        ->name('supplier_staff.staff.destroy');
});

/*
|--------------------------------------------------------------------------
| Protected Supplier Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:supplier_staff')->prefix('suppliers')->group(function () {
    // Event Management
    Route::get('/events', [EventController::class, 'index'])
        ->name('suppliers.events.index');

    Route::post('/events', [EventController::class, 'store'])
        ->name('suppliers.events.store');

    Route::put('/events/{id}', [EventController::class, 'update'])
        ->name('suppliers.events.update');
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
