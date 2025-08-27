<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Dashboard routes
Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    
    // Subscription management routes
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [DashboardController::class, 'subscriptions'])->name('index');
        Route::get('/create', [DashboardController::class, 'createSubscription'])->name('create');
        Route::get('/{subscription}', [DashboardController::class, 'showSubscription'])->name('show');
        Route::get('/{subscription}/edit', [DashboardController::class, 'editSubscription'])->name('edit');
    });
    
    // Event management routes
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [DashboardController::class, 'events'])->name('index');
        Route::get('/{event}', [DashboardController::class, 'showEvent'])->name('show');
    });
    
    // Delivery management routes
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [DashboardController::class, 'deliveries'])->name('index');
        Route::get('/{delivery}', [DashboardController::class, 'showDelivery'])->name('show');
    });
    
    // API Key management route
    Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('api-keys');
    
    // User management routes
    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::post('/users/{user}/generate-api-key', [\App\Http\Controllers\UserController::class, 'generateApiKey'])->name('users.generate-api-key');
    Route::delete('/users/{user}/revoke-api-key', [\App\Http\Controllers\UserController::class, 'revokeApiKey'])->name('users.revoke-api-key');
});
