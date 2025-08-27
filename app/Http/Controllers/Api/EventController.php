<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Display a listing of events.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'event_type' => 'string|max:255',
            'status' => 'string|in:pending,processing,completed,failed',
            'search' => 'string|max:255',
            'date_from' => 'date',
            'date_to' => 'date|after_or_equal:date_from',
        ]);

        $query = Event::forTenant(Auth::id())
            ->with(['deliveries' => function ($query) {
                $query->latest()->limit(5);
            }]);

        if ($request->filled('event_type')) {
            $query->byEventType($request->event_type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('event_type', 'like', "%{$search}%")
                  ->orWhere('idempotency_key', 'like', "%{$search}%")
                  ->orWhereJsonContains('payload', $search);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $events = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($events);
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'event_type' => 'required|string|max:255',
                'payload' => 'required|array',
                'idempotency_key' => 'nullable|string|max:255',
            ]);

            $event = $this->webhookService->createEvent(
                $validated['event_type'],
                $validated['payload'],
                Auth::id(),
                $validated['idempotency_key'] ?? null
            );

            return response()->json([
                'message' => 'Event created and dispatched successfully',
                'data' => $event->load('deliveries')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified event.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $event = Event::forTenant(Auth::id())
                ->with([
                    'deliveries.subscription:id,name,url',
                    'deliveries.deadLetter'
                ])
                ->findOrFail($id);

            return response()->json([
                'data' => $event
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Event not found'
            ], 404);
        }
    }

    /**
     * Get event statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'date',
            'date_to' => 'date|after_or_equal:date_from',
            'event_type' => 'string|max:255',
        ]);

        $query = Event::forTenant(Auth::id());

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('event_type')) {
            $query->byEventType($request->event_type);
        }

        $stats = [
            'total_events' => $query->count(),
            'by_status' => $query->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_event_type' => $query->selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'event_type'),
            'recent_activity' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderByDesc('date')
                ->limit(30)
                ->pluck('count', 'date'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Retry a failed event.
     */
    public function retry(string $id): JsonResponse
    {
        try {
            $event = Event::forTenant(Auth::id())
                ->where('status', 'failed')
                ->findOrFail($id);

            // Reset event status and dispatch again
            $event->update(['status' => 'pending']);
            
            \App\Jobs\DispatchEventDeliveries::dispatch($event);

            return response()->json([
                'message' => 'Event retry initiated successfully',
                'data' => $event->fresh()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Failed event not found'
            ], 404);
        }
    }

    /**
     * Get deliveries for a specific event.
     */
    public function deliveries(string $id, Request $request): JsonResponse
    {
        try {
            $event = Event::forTenant(Auth::id())->findOrFail($id);

            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'status' => 'string|in:pending,success,failed,retrying',
            ]);

            $query = $event->deliveries()
                ->with(['subscription:id,name,url', 'deadLetter']);

            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            $deliveries = $query->latest()
                ->paginate($request->get('per_page', 15));

            return response()->json($deliveries);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Event not found'
            ], 404);
        }
    }

    /**
     * Get event types available for the tenant.
     */
    public function eventTypes(): JsonResponse
    {
        $eventTypes = Event::forTenant(Auth::id())
            ->distinct()
            ->pluck('event_type')
            ->sort()
            ->values();

        return response()->json([
            'data' => $eventTypes
        ]);
    }
}
