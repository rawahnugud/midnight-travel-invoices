<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/setup/seed', [SetupController::class, 'seed'])->name('setup.seed');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/settings', fn () => view('settings'))->name('settings');

    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::resource('invoices', InvoiceController::class)->names('invoices');

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit'])->names('users');
        Route::get('/settings/business', [BusinessSettingsController::class, 'edit'])->name('settings.business.edit');
        Route::put('/settings/business', [BusinessSettingsController::class, 'update'])->name('settings.business.update');
        Route::get('/settings/design', [BusinessSettingsController::class, 'editDesign'])->name('settings.design.edit');
        Route::put('/settings/design', [BusinessSettingsController::class, 'updateDesign'])->name('settings.design.update');
    });
});

Route::fallback(fn () => response()->view('404', [], 404)->header('Content-Type', 'text/html'));
