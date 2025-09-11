<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Project;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook requests
     */
    public function handle(Request $request, string $urlPath)
    {
        try {
            // Find the endpoint by URL path
            $endpoint = WebhookEndpoint::where('url_path', $urlPath)
                ->where('is_active', true)
                ->with('project')
                ->first();

            if (!$endpoint) {
                return response()->json(['error' => 'Webhook endpoint not found'], 404);
            }

            $project = $endpoint->project;
            
            if (!$project->is_active) {
                return response()->json(['error' => 'Project is inactive'], 404);
            }

            // Authenticate the request
            if (!$this->authenticateRequest($request, $endpoint)) {
                return response()->json(['error' => 'Authentication failed'], 401);
            }

            // Create the event record
            $event = $this->createEvent($request, $project, $endpoint);

            // Dispatch the event for processing
            ProcessWebhookEvent::dispatch($event);

            Log::info('Webhook received', [
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'event_id' => $event->event_id,
            ]);

            return response()->json([
                'success' => true,
                'event_id' => $event->event_id,
                'message' => 'Webhook received and queued for processing'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'url_path' => $urlPath,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Authenticate the webhook request
     */
    private function authenticateRequest(Request $request, WebhookEndpoint $endpoint): bool
    {
        if ($endpoint->auth_method === 'none') {
            return true;
        }

        if ($endpoint->auth_method === 'hmac') {
            return $this->validateHmacSignature($request, $endpoint);
        }

        if ($endpoint->auth_method === 'shared_secret') {
            return $this->validateSharedSecret($request, $endpoint);
        }

        return false;
    }

    /**
     * Validate HMAC signature
     */
    private function validateHmacSignature(Request $request, WebhookEndpoint $endpoint): bool
    {
        $signature = $request->header('X-Signature-256') ?? $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $secret = $endpoint->auth_config['secret'] ?? '';
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate shared secret
     */
    private function validateSharedSecret(Request $request, WebhookEndpoint $endpoint): bool
    {
        $secret = $request->header('X-Webhook-Secret') ?? $request->input('secret');
        $expectedSecret = $endpoint->auth_config['secret'] ?? '';
        
        return hash_equals($expectedSecret, $secret ?? '');
    }

    /**
     * Create event record
     */
    private function createEvent(Request $request, Project $project, WebhookEndpoint $endpoint): Event
    {
        return Event::create([
            'project_id' => $project->id,
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => $request->header('X-Event-Type') ?? 'webhook',
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'source_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'pending',
        ]);
    }

    /**
     * Get webhook endpoint info (for debugging)
     */
    public function info(Request $request, string $urlPath)
    {
        $endpoint = WebhookEndpoint::where('url_path', $urlPath)
            ->with('project')
            ->first();
        
        if (!$endpoint) {
            return response()->json(['error' => 'Webhook endpoint not found'], 404);
        }

        return response()->json([
            'project' => $endpoint->project->name,
            'endpoint' => $endpoint->name,
            'url_path' => $endpoint->url_path,
            'short_url' => $endpoint->short_url,
            'auth_method' => $endpoint->auth_method,
            'is_active' => $endpoint->is_active,
            'destination_urls' => $endpoint->destination_urls,
        ]);
    }

    /**
     * Handle incoming webhook via short URL
     */
    public function handleShort(Request $request, string $shortUrl)
    {
        try {
            // Find the endpoint by short URL
            $endpoint = WebhookEndpoint::where('short_url', $shortUrl)
                ->where('is_active', true)
                ->with('project')
                ->first();

            if (!$endpoint) {
                return response()->json(['error' => 'Webhook endpoint not found'], 404);
            }

            $project = $endpoint->project;
            
            if (!$project->is_active) {
                return response()->json(['error' => 'Project is inactive'], 404);
            }

            // Authenticate the request
            if (!$this->authenticateRequest($request, $endpoint)) {
                return response()->json(['error' => 'Authentication failed'], 401);
            }

            // Create the event record
            $event = $this->createEvent($request, $project, $endpoint);

            // Dispatch the event for processing
            ProcessWebhookEvent::dispatch($event);

            Log::info('Webhook received via short URL', [
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'event_id' => $event->event_id,
                'short_url' => $shortUrl,
            ]);

            return response()->json([
                'success' => true,
                'event_id' => $event->event_id,
                'message' => 'Webhook received and queued for processing'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing error (short URL)', [
                'error' => $e->getMessage(),
                'short_url' => $shortUrl,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Get webhook endpoint info via short URL
     */
    public function infoShort(string $shortUrl)
    {
        $endpoint = WebhookEndpoint::where('short_url', $shortUrl)
            ->where('is_active', true)
            ->with('project')
            ->first();

        if (!$endpoint) {
            return response()->json(['error' => 'Webhook endpoint not found'], 404);
        }

        return response()->json([
            'project' => $endpoint->project->name,
            'endpoint' => $endpoint->name,
            'url_path' => $endpoint->url_path,
            'short_url' => $endpoint->short_url,
            'auth_method' => $endpoint->auth_method,
            'is_active' => $endpoint->is_active,
            'destination_urls' => $endpoint->destination_urls,
        ]);
    }
}
