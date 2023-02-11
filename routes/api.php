<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

  
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\LoadController;
use App\Http\Controllers\API\GoogleLoginController;
use App\Http\Controllers\API\AppleLoginController;
use App\Http\Controllers\API\VerificationController;
use App\Http\Controllers\API\PasswordResetRequestController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\QuickBooksController;
use App\Http\Controllers\API\PriceController;

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
Route::delete('account/delete', [RegisterController::class, 'delete'])->middleware('auth:api');

Route::post('google/login', [GoogleLoginController::class, 'login']);
Route::post('apple/login', [AppleLoginController::class, 'login']);
Route::middleware(['auth:api','verified'])->group( function () {
    Route::resource('loads', LoadController::class);
    Route::post('loads/{id}/counter-rate', [LoadController::class , 'handleCounterRate']);
    Route::post('loads/distance', [LoadController::class , 'getDistanceBetweenPoints']);
});


Route::get('/email/verify',[VerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
Route::post('/forgot-password', [PasswordResetRequestController::class , 'sendPasswordResetEmail'])->middleware('guest')->name('password.email');
Route::post('/reset-password', [PasswordResetRequestController::class , 'resetPassword'])->middleware('guest')->name('password.update');

Route::patch('/location/{id}', [LocationController::class, 'update'])->middleware('auth:api');
Route::patch('/account', [UserController::class, 'update'])->middleware('auth:api');
Route::post('/account/company', [UserController::class, 'updateCompany'])->middleware('auth:api');
Route::get('/rates', [PriceController::class, 'getRates'])->middleware('auth:api');

Route::middleware(['auth:api','verified'])->group( function () {
    Route::post('/customer/create', [QuickBooksController::class , 'createCustomer']);
    Route::post('/customer/{id}/update', [QuickBooksController::class , 'updateCustomer']);
    Route::post('/invoice/create', [QuickBooksController::class , 'createInvoice']);
    Route::post('/invoice/{id}/update', [QuickBooksController::class , 'updateInvoice']);
});

// admin specific actions 
Route::get('admin/loads', [LoadController::class , 'getLoadList'])->middleware(['auth:api','admin','verified']);
Route::get('admin/customers', [UserController::class , 'getCustomers'])->middleware(['auth:api','admin','verified']);
Route::get('admin/customer/{id}', [UserController::class , 'getCustomerInfo'])->middleware(['auth:api','admin','verified']);
Route::patch('admin/load/{id}/status', [LoadController::class , 'updateLoadStatus'])->middleware(['auth:api','admin','verified']);
Route::post('admin/load/{id}/counter-rate', [LoadController::class , 'handleCounterRateAgainstCustomer'])->middleware(['auth:api','admin','verified']);