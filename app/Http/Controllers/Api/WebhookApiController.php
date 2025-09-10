<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Project;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookApiController extends Controller
{
    /**
     * Create a new webhook endpoint
     */
    public function createEndpoint(Request $request): JsonResponse
    {
        try {
            // Get project from API key
            $project = $this->getProjectFromApiKey($request);
            if (!$project) {
                return response()->json(['error' => 'Invalid API key'], 401);
            }

            // Validate request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'destination_urls' => 'required|array|min:1',
                'destination_urls.*' => 'required|url',
                'auth_method' => 'required|in:none,hmac,shared_secret',
                'auth_secret' => 'required_unless:auth_method,none|string',
                'retry_config' => 'sometimes|array',
                'headers_config' => 'sometimes|array',
                'is_active' => 'sometimes|boolean',
            ]);

            // Create webhook endpoint
            $endpoint = WebhookEndpoint::create([
                'project_id' => $project->id,
                'name' => $validated['name'],
                'destination_urls' => $validated['destination_urls'],
                'auth_method' => $validated['auth_method'],
                'auth_secret' => $validated['auth_secret'] ?? null,
                'retry_config' => $validated['retry_config'] ?? [
                    'max_attempts' => 3,
                    'retry_delay' => 60,
                    'backoff_multiplier' => 2
                ],
                'headers_config' => $validated['headers_config'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::info('Webhook endpoint created via API', [
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'name' => $endpoint->name
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $endpoint->id,
                    'name' => $endpoint->name,
                    'slug' => $endpoint->slug,
                    'url_path' => $endpoint->url_path,
                    'webhook_url' => url('/api' . $endpoint->url_path),
                    'destination_urls' => $endpoint->destination_urls,
                    'auth_method' => $endpoint->auth_method,
                    'is_active' => $endpoint->is_active,
                    'created_at' => $endpoint->created_at,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating webhook endpoint via API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Get events for a project with filtering
     */
    public function getEvents(Request $request): JsonResponse
    {
        try {
            // Get project from API key
            $project = $this->getProjectFromApiKey($request);
            if (!$project) {
                return response()->json(['error' => 'Invalid API key'], 401);
            }

            // Validate filters
            $validated = $request->validate([
                'status' => 'sometimes|in:pending,processing,delivered,failed',
                'event_type' => 'sometimes|string|max:255',
                'webhook_endpoint_id' => 'sometimes|integer|exists:webhook_endpoints,id',
                'from_date' => 'sometimes|date',
                'to_date' => 'sometimes|date|after_or_equal:from_date',
                'limit' => 'sometimes|integer|min:1|max:1000',
                'offset' => 'sometimes|integer|min:0',
                'sort_by' => 'sometimes|in:created_at,updated_at,delivered_at',
                'sort_order' => 'sometimes|in:asc,desc',
            ]);

            // Build query
            $query = Event::where('project_id', $project->id)
                ->with(['webhookEndpoint:id,name,slug']);

            // Apply filters
            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (isset($validated['event_type'])) {
                $query->where('event_type', $validated['event_type']);
            }

            if (isset($validated['webhook_endpoint_id'])) {
                $query->where('webhook_endpoint_id', $validated['webhook_endpoint_id']);
            }

            if (isset($validated['from_date'])) {
                $query->where('created_at', '>=', $validated['from_date']);
            }

            if (isset($validated['to_date'])) {
                $query->where('created_at', '<=', $validated['to_date']);
            }

            // Apply sorting
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Apply pagination
            $limit = $validated['limit'] ?? 50;
            $offset = $validated['offset'] ?? 0;
            
            $totalCount = $query->count();
            $events = $query->offset($offset)->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events->map(function ($event) {
                        return [
                            'id' => $event->id,
                            'event_id' => $event->event_id,
                            'event_type' => $event->event_type,
                            'status' => $event->status,
                            'webhook_endpoint' => $event->webhookEndpoint ? [
                                'id' => $event->webhookEndpoint->id,
                                'name' => $event->webhookEndpoint->name,
                                'slug' => $event->webhookEndpoint->slug,
                            ] : null,
                            'payload' => $event->payload,
                            'headers' => $event->headers,
                            'source_ip' => $event->source_ip,
                            'user_agent' => $event->user_agent,
                            'delivery_attempts' => $event->delivery_attempts,
                            'last_attempt_at' => $event->last_attempt_at,
                            'delivered_at' => $event->delivered_at,
                            'created_at' => $event->created_at,
                            'updated_at' => $event->updated_at,
                        ];
                    }),
                    'pagination' => [
                        'total' => $totalCount,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $totalCount,
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error getting events via API', [
                'error' => $e->getMessage(),
                'project_id' => $project->id ?? null
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Replay a single event
     */
    public function replayEvent(Request $request, int $eventId): JsonResponse
    {
        try {
            // Get project from API key
            $project = $this->getProjectFromApiKey($request);
            if (!$project) {
                return response()->json(['error' => 'Invalid API key'], 401);
            }

            // Find the event
            $event = Event::where('id', $eventId)
                ->where('project_id', $project->id)
                ->with('webhookEndpoint')
                ->first();

            if (!$event) {
                return response()->json(['error' => 'Event not found'], 404);
            }

            if (!$event->webhookEndpoint || !$event->webhookEndpoint->is_active) {
                return response()->json(['error' => 'Webhook endpoint is inactive'], 400);
            }

            // Reset event status and dispatch for reprocessing
            $event->update([
                'status' => 'pending',
                'delivery_attempts' => 0,
                'last_attempt_at' => null,
                'delivered_at' => null,
            ]);

            ProcessWebhookEvent::dispatch($event);

            Log::info('Event replayed via API', [
                'project_id' => $project->id,
                'event_id' => $event->event_id,
                'endpoint_id' => $event->webhook_endpoint_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event queued for replay',
                'data' => [
                    'event_id' => $event->event_id,
                    'status' => $event->status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error replaying event via API', [
                'error' => $e->getMessage(),
                'event_id' => $eventId,
                'project_id' => $project->id ?? null
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Replay multiple events using filters
     */
    public function replayEvents(Request $request): JsonResponse
    {
        try {
            // Get project from API key
            $project = $this->getProjectFromApiKey($request);
            if (!$project) {
                return response()->json(['error' => 'Invalid API key'], 401);
            }

            // Validate filters
            $validated = $request->validate([
                'status' => 'sometimes|in:failed,delivered',
                'event_type' => 'sometimes|string|max:255',
                'webhook_endpoint_id' => 'sometimes|integer|exists:webhook_endpoints,id',
                'from_date' => 'sometimes|date',
                'to_date' => 'sometimes|date|after_or_equal:from_date',
                'event_ids' => 'sometimes|array|max:100',
                'event_ids.*' => 'integer',
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);

            // Build query
            $query = Event::where('project_id', $project->id)
                ->with('webhookEndpoint');

            // Apply filters
            if (isset($validated['event_ids'])) {
                $query->whereIn('id', $validated['event_ids']);
            } else {
                // Apply other filters only if event_ids is not specified
                if (isset($validated['status'])) {
                    $query->where('status', $validated['status']);
                }

                if (isset($validated['event_type'])) {
                    $query->where('event_type', $validated['event_type']);
                }

                if (isset($validated['webhook_endpoint_id'])) {
                    $query->where('webhook_endpoint_id', $validated['webhook_endpoint_id']);
                }

                if (isset($validated['from_date'])) {
                    $query->where('created_at', '>=', $validated['from_date']);
                }

                if (isset($validated['to_date'])) {
                    $query->where('created_at', '<=', $validated['to_date']);
                }
            }

            // Apply limit
            $limit = $validated['limit'] ?? 50;
            $events = $query->limit($limit)->get();

            if ($events->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No events found matching the criteria',
                    'data' => ['replayed_count' => 0]
                ]);
            }

            $replayedCount = 0;
            $errors = [];

            foreach ($events as $event) {
                try {
                    if (!$event->webhookEndpoint || !$event->webhookEndpoint->is_active) {
                        $errors[] = "Event {$event->event_id}: Webhook endpoint is inactive";
                        continue;
                    }

                    // Reset event status and dispatch for reprocessing
                    $event->update([
                        'status' => 'pending',
                        'delivery_attempts' => 0,
                        'last_attempt_at' => null,
                        'delivered_at' => null,
                    ]);

                    ProcessWebhookEvent::dispatch($event);
                    $replayedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Event {$event->event_id}: {$e->getMessage()}";
                }
            }

            Log::info('Multiple events replayed via API', [
                'project_id' => $project->id,
                'replayed_count' => $replayedCount,
                'total_events' => $events->count(),
                'errors_count' => count($errors)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Replayed {$replayedCount} events",
                'data' => [
                    'replayed_count' => $replayedCount,
                    'total_events' => $events->count(),
                    'errors' => $errors
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error replaying multiple events via API', [
                'error' => $e->getMessage(),
                'project_id' => $project->id ?? null
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Get project from API key in request header
     */
    private function getProjectFromApiKey(Request $request): ?Project
    {
        $apiKey = $request->header('X-API-Key') ?? $request->header('Authorization');
        
        // Handle Bearer token format
        if ($apiKey && str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        if (!$apiKey) {
            return null;
        }

        return Project::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();
    }
}