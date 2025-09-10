<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\WebhookApiController;
use App\Http\Controllers\HealthController;

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

// Webhook ingestion endpoint
Route::post('/webhook/{urlPath}', [WebhookController::class, 'handleIncomingWebhook'])
    ->where('urlPath', '[a-zA-Z0-9\-_]+');

// Get webhook endpoint info
Route::get('/webhook/{urlPath}/info', [WebhookController::class, 'getWebhookInfo'])
    ->where('urlPath', '[a-zA-Z0-9\-_]+');

// Webhook Management API (requires API key authentication)
Route::prefix('v1')->middleware('api.auth')->group(function () {
    // Create webhook endpoint
    Route::post('/webhooks/endpoints', [WebhookApiController::class, 'createEndpoint']);
    
    // Get events for a project
    Route::get('/events', [WebhookApiController::class, 'getEvents']);
    
    // Replay single event
    Route::post('/events/{eventId}/replay', [WebhookApiController::class, 'replayEvent'])
        ->where('eventId', '[0-9]+');
    
    // Replay multiple events
    Route::post('/events/replay', [WebhookApiController::class, 'replayEvents']);
});

// Health check
Route::get('/health', [HealthController::class, 'check']);