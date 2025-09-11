<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookTransformationController;
use App\Http\Controllers\WebhookRoutingRuleController;
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
    Route::post('/dashboard/endpoints/{endpoint}/test', [DashboardController::class, 'testEndpoint'])->name('dashboard.endpoints.test');
    Route::get('/dashboard/endpoints/{endpoint}/events', [DashboardController::class, 'endpointEvents'])->name('dashboard.endpoints.events');
    
    // Webhook Transformations
    Route::get('/dashboard/endpoints/{endpoint}/transformations', [WebhookTransformationController::class, 'index'])->name('transformations.index');
    Route::get('/dashboard/endpoints/{endpoint}/transformations/create', [WebhookTransformationController::class, 'create'])->name('transformations.create');
    Route::post('/dashboard/endpoints/{endpoint}/transformations', [WebhookTransformationController::class, 'store'])->name('transformations.store');
    Route::get('/dashboard/endpoints/{endpoint}/transformations/{transformation}/edit', [WebhookTransformationController::class, 'edit'])->name('transformations.edit');
    Route::patch('/dashboard/endpoints/{endpoint}/transformations/{transformation}', [WebhookTransformationController::class, 'update'])->name('transformations.update');
    Route::delete('/dashboard/endpoints/{endpoint}/transformations/{transformation}', [WebhookTransformationController::class, 'destroy'])->name('transformations.destroy');
    Route::post('/dashboard/endpoints/{endpoint}/transformations/{transformation}/test', [WebhookTransformationController::class, 'test'])->name('transformations.test');
    Route::patch('/dashboard/endpoints/{endpoint}/transformations/{transformation}/toggle', [WebhookTransformationController::class, 'toggle'])->name('transformations.toggle');
    Route::post('/dashboard/endpoints/{endpoint}/transformations/{transformation}/duplicate', [WebhookTransformationController::class, 'duplicate'])->name('transformations.duplicate');
    
    // Webhook Routing Rules
    Route::get('/dashboard/endpoints/{endpoint}/routing-rules', [WebhookRoutingRuleController::class, 'index'])->name('routing-rules.index');
    Route::get('/dashboard/endpoints/{endpoint}/routing-rules/create', [WebhookRoutingRuleController::class, 'create'])->name('routing-rules.create');
    Route::post('/dashboard/endpoints/{endpoint}/routing-rules', [WebhookRoutingRuleController::class, 'store'])->name('routing-rules.store');
    Route::get('/dashboard/endpoints/{endpoint}/routing-rules/{routingRule}/edit', [WebhookRoutingRuleController::class, 'edit'])->name('routing-rules.edit');
    Route::patch('/dashboard/endpoints/{endpoint}/routing-rules/{routingRule}', [WebhookRoutingRuleController::class, 'update'])->name('routing-rules.update');
    Route::delete('/dashboard/endpoints/{endpoint}/routing-rules/{routingRule}', [WebhookRoutingRuleController::class, 'destroy'])->name('routing-rules.destroy');
    Route::patch('/dashboard/endpoints/{endpoint}/routing-rules/{routingRule}/toggle', [WebhookRoutingRuleController::class, 'toggle'])->name('routing-rules.toggle');
    Route::post('/dashboard/endpoints/{endpoint}/routing-rules/{routingRule}/duplicate', [WebhookRoutingRuleController::class, 'duplicate'])->name('routing-rules.duplicate');
    
    // Settings
    Route::get('/dashboard/settings', [SettingsController::class, 'index'])->name('dashboard.settings');
    Route::post('/dashboard/settings', [SettingsController::class, 'update'])->name('dashboard.settings.update');
    Route::put('/dashboard/settings/general', [SettingsController::class, 'updateGeneral'])->name('dashboard.settings.general.update');
    Route::put('/dashboard/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('dashboard.settings.notifications.update');
    Route::put('/dashboard/settings/security', [SettingsController::class, 'updateSecurity'])->name('dashboard.settings.security.update');
    
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
