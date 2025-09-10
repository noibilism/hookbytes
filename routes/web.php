<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Protected dashboard routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Main dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Events
    Route::get('/dashboard/events', [DashboardController::class, 'events'])->name('dashboard.events');
    Route::get('/dashboard/events/{event}', [DashboardController::class, 'showEvent'])->name('dashboard.events.show');
    Route::post('/dashboard/events/{event}/replay', [DashboardController::class, 'replayEvent'])->name('dashboard.events.replay');
    Route::post('/dashboard/events/bulk-retry', [DashboardController::class, 'bulkRetryEvents'])->name('dashboard.events.bulk-retry');
    
    // Chart data endpoints
    Route::get('/dashboard/events-chart', [DashboardController::class, 'eventsChart'])->name('dashboard.events-chart');
    Route::get('/dashboard/projects/{project}/events-chart', [DashboardController::class, 'projectEventsChart'])->name('dashboard.projects.events-chart');
    
    // Projects
    Route::get('/dashboard/projects', [DashboardController::class, 'projects'])->name('dashboard.projects');
    Route::get('/dashboard/projects/create', [DashboardController::class, 'createProject'])->name('dashboard.projects.create');
    Route::post('/dashboard/projects', [DashboardController::class, 'storeProject'])->name('dashboard.projects.store');
    Route::get('/dashboard/projects/{project}', [DashboardController::class, 'showProject'])->name('dashboard.projects.show');
    Route::delete('/dashboard/projects/{project}', [DashboardController::class, 'destroyProject'])->name('dashboard.projects.destroy');
    
    // Webhook Endpoints
    Route::get('/dashboard/projects/{project}/endpoints/create', [DashboardController::class, 'createEndpoint'])->name('dashboard.endpoints.create');
    Route::post('/dashboard/projects/{project}/endpoints', [DashboardController::class, 'storeEndpoint'])->name('dashboard.endpoints.store');
    Route::get('/dashboard/endpoints/{endpoint}/edit', [DashboardController::class, 'editEndpoint'])->name('dashboard.endpoints.edit');
    Route::patch('/dashboard/endpoints/{endpoint}', [DashboardController::class, 'updateEndpoint'])->name('dashboard.endpoints.update');
    Route::delete('/dashboard/endpoints/{endpoint}', [DashboardController::class, 'deleteEndpoint'])->name('dashboard.endpoints.destroy');
    
    // Settings
    Route::get('/dashboard/settings', [SettingsController::class, 'index'])->name('dashboard.settings');
    Route::post('/dashboard/settings', [SettingsController::class, 'update'])->name('dashboard.settings.update');
    
    // API Documentation
    Route::get('/dashboard/api-docs', [DashboardController::class, 'apiDocs'])->name('dashboard.api-docs');
    
    // Event Details
    Route::get('/dashboard/event-details/{event}', [DashboardController::class, 'eventDetails'])->name('dashboard.event-details');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // User Management Routes
    Route::get('/dashboard/users', [UserController::class, 'index'])->name('dashboard.users.index');
    Route::post('/dashboard/users', [UserController::class, 'store'])->name('dashboard.users.store');
    Route::delete('/dashboard/users/{id}', [UserController::class, 'destroy'])->name('dashboard.users.destroy');
});

require __DIR__.'/auth.php';
