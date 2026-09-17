<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Client\ClientUserController;
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
Route::post('/supplier_staff/login', [SupplierStaffUserController::class, 'login'])
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

Route::middleware('auth:supplier_staff')->prefix('supplier_staff')->group(function () {
    Route::get('/profile', [SupplierStaffUserController::class, 'profile'])
        ->name('supplier_staff.profile');
    
    Route::put('/profile', [SupplierStaffUserController::class, 'updateProfile'])
        ->name('supplier_staff.update_profile');
    
    Route::put('/password', [SupplierStaffUserController::class, 'changePassword'])
        ->name('supplier_staff.change_password');
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
