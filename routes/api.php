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

// Webhook ingestion endpoint (original format)
Route::post('/webhook/{urlPath}', [WebhookController::class, 'handle'])
    ->where('urlPath', '[a-zA-Z0-9\-_/]+');

// Webhook ingestion endpoint (short URL format)
Route::post('/w/{shortUrl}', [WebhookController::class, 'handleShort'])
    ->where('shortUrl', '[a-zA-Z0-9]{8}');

// Get webhook endpoint info (original format)
Route::get('/webhook/{urlPath}/info', [WebhookController::class, 'info'])
    ->where('urlPath', '[a-zA-Z0-9\-_/]+');

// Get webhook endpoint info (short URL format)
Route::get('/w/{shortUrl}/info', [WebhookController::class, 'infoShort'])
    ->where('shortUrl', '[a-zA-Z0-9]{8}');

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