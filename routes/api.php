<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

  
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\LoadController;
use App\Http\Controllers\API\GoogleLoginController;
use App\Http\Controllers\API\VerificationController;
use App\Http\Controllers\API\PasswordResetRequestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('account', [RegisterController::class, 'account'])->middleware('auth:api');
Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login']);

Route::post('google/login', [GoogleLoginController::class, 'login']);
Route::middleware(['auth:api','verified'])->group( function () {
    Route::resource('loads', LoadController::class);
    Route::post('loads/{id}/counter-rate', [LoadController::class , 'handleCounterRate']);
});

Route::get('/email/verify',[VerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
Route::post('/forgot-password', [PasswordResetRequestController::class , 'sendPasswordResetEmail'])->middleware('guest')->name('password.email');
Route::post('/reset-password', [PasswordResetRequestController::class , 'resetPassword'])->middleware('guest')->name('password.update');