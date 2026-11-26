<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\CustomerBalanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TeamReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/v/{id}', \App\Http\Controllers\VerifyInvoiceController::class)
    ->whereNumber('id')->name('invoice.verify');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ar'], true)) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/settings', fn () => view('settings'))->name('settings');

    Route::get('/invoices/customer-balance', CustomerBalanceController::class)->name('invoices.customer-balance');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/payments/{payment}', [PaymentController::class, 'show'])
        ->scopeBindings()->name('invoices.payments.show');
    Route::middleware('role:admin,staff')->group(function () {
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
            ->name('invoices.payments.store');
        Route::resource('invoices', InvoiceController::class)->except(['index', 'show']);
    });
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/invoices/{invoice}/payments/{payment}/reverse', [PaymentController::class, 'reverse'])
            ->scopeBindings()->name('invoices.payments.reverse');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::get('/reports/team', TeamReportController::class)->name('reports.team');
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit'])->names('users');
        Route::get('/settings/business', [BusinessSettingsController::class, 'edit'])->name('settings.business.edit');
        Route::put('/settings/business/branches/{branch}', [BusinessSettingsController::class, 'updateBranch'])
            ->whereIn('branch', ['AE', 'GB'])->name('settings.branch.update');
        Route::put('/settings/business', [BusinessSettingsController::class, 'update'])->name('settings.business.update');
        Route::get('/settings/design', [BusinessSettingsController::class, 'editDesign'])->name('settings.design.edit');
        Route::put('/settings/design', [BusinessSettingsController::class, 'updateDesign'])->name('settings.design.update');
    });
});

Route::fallback(fn () => response()->view('404', [], 404)->header('Content-Type', 'text/html'));
