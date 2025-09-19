<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\CredentialController;
use Illuminate\Support\Facades\Auth;

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

Route::get('/', function () {
    return view('welcome');
});

// Admin Routes
Route::prefix('admin')->group(function () {
    // Public admin routes
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'login'])->name('admin.login.submit');
    
    // Authenticated admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
        Route::get('/orders/{id}/edit', [AdminController::class, 'editOrder'])->name('admin.orders.edit');
        Route::put('/orders/{id}', [AdminController::class, 'updateOrder'])->name('admin.orders.update');
        Route::patch('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.status');
        Route::delete('/orders/{id}', [AdminController::class, 'deleteOrder'])->name('admin.orders.delete');
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');
        
        // Logs management
        Route::get('/logs', [AdminController::class, 'logs'])->name('admin.logs');
        Route::delete('/logs/clear', [AdminController::class, 'clearLogs'])->name('admin.logs.clear');
        
        // User management routes
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
        Route::patch('/users/{id}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('admin.users.toggle-admin');
        Route::patch('/users/{id}/reset-password', [AdminController::class, 'resetPassword'])->name('admin.users.reset-password');
        
        // Credential management routes
        Route::get('/credentials', [CredentialController::class, 'index'])->name('admin.credentials');
        Route::get('/credentials/create', [CredentialController::class, 'create'])->name('admin.credentials.create');
        Route::post('/credentials', [CredentialController::class, 'store'])->name('admin.credentials.store');
        Route::get('/credentials/{id}/edit', [CredentialController::class, 'edit'])->name('admin.credentials.edit');
        Route::put('/credentials/{id}', [CredentialController::class, 'update'])->name('admin.credentials.update');
        Route::delete('/credentials/{id}', [CredentialController::class, 'destroy'])->name('admin.credentials.destroy');
    });
});

// Add fallback login route
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/admin/login')->with('success', 'You have been logged out successfully.');
})->name('logout');

// QuickBooks OAuth Routes
Route::get('/quickbooks/connect', [\App\Http\Controllers\QuickBooksAuthController::class, 'connect'])->name('quickbooks.connect');
Route::get('/quickbooks/callback', [\App\Http\Controllers\QuickBooksAuthController::class, 'callback'])->name('quickbooks.callback');
Route::get('/quickbooks/status', [\App\Http\Controllers\QuickBooksAuthController::class, 'status'])->name('quickbooks.status');