<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
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