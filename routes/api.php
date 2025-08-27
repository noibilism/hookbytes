<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HookController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ReplayController;
use App\Http\Controllers\Api\ApiKeyController;

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

Route::middleware('auth.apikey')->get('/user', function (Request $request) {
    return $request->user();
});

// Protected API routes
Route::middleware('auth.apikey')->group(function () {
    
    // API Key Management
    Route::prefix('apikey')->group(function () {
        Route::get('/', [ApiKeyController::class, 'show']);
        Route::post('/generate', [ApiKeyController::class, 'generate']);
        Route::post('/regenerate', [ApiKeyController::class, 'regenerate']);
        Route::delete('/revoke', [ApiKeyController::class, 'revoke']);
    });
    
    // Webhook Subscriptions (Hooks)
    Route::apiResource('hooks', HookController::class);
    Route::post('hooks/{id}/toggle', [HookController::class, 'toggle']);
    Route::post('hooks/{id}/test', [HookController::class, 'test']);
    
    // Events
    Route::apiResource('events', EventController::class)->except(['update', 'destroy']);
    Route::get('events/stats', [EventController::class, 'stats']);
    Route::post('events/{id}/retry', [EventController::class, 'retry']);
    Route::get('events/{id}/deliveries', [EventController::class, 'deliveries']);
    Route::get('event-types', [EventController::class, 'eventTypes']);
    
    // Replay Operations
    Route::get('replays', [ReplayController::class, 'index']);
    Route::post('replays/{id}', [ReplayController::class, 'replay']);
    Route::post('replays/bulk', [ReplayController::class, 'bulkReplay']);
    Route::get('replays/stats', [ReplayController::class, 'stats']);
    Route::post('replays/preview', [ReplayController::class, 'preview']);
    
});

// Public webhook endpoint (no authentication required)
Route::post('webhook', function (Request $request) {
    // This would be handled by a separate controller for receiving webhooks
    // For now, just return a basic response
    return response()->json(['message' => 'Webhook endpoint - implement webhook receiver here']);
});
