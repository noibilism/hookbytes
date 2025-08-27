<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Jobs\AttemptDelivery;
use App\Jobs\BulkReplayDeliveries;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ReplayController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Display a listing of deliveries available for replay.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'subscription_id' => 'string|exists:subscriptions,id',
            'event_type' => 'string|max:255',
            'status' => 'string|in:failed,success',
            'date_from' => 'date',
            'date_to' => 'date|after_or_equal:date_from',
            'response_code' => 'integer',
        ]);

        $query = Delivery::whereHas('event', function ($q) {
                $q->forTenant(Auth::id());
            })
            ->with([
                'event:id,event_type,created_at',
                'subscription:id,name,url',
                'deadLetter'
            ]);

        if ($request->filled('subscription_id')) {
            $query->forSubscription($request->subscription_id);
        }

        if ($request->filled('event_type')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->byEventType($request->event_type);
            });
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('response_code')) {
            $query->where('response_code', $request->response_code);
        }

        $deliveries = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($deliveries);
    }

    /**
     * Replay a single delivery.
     */
    public function replay(string $id): JsonResponse
    {
        try {
            $delivery = Delivery::whereHas('event', function ($q) {
                    $q->forTenant(Auth::id());
                })
                ->with(['event', 'subscription'])
                ->findOrFail($id);

            // Reset delivery for replay
            $delivery->update([
                'status' => 'pending',
                'attempt' => 1,
                'response_code' => null,
                'response_body' => null,
                'duration_ms' => null,
                'next_retry_at' => now(),
                'updated_at' => now()
            ]);

            // Dispatch the delivery job
            AttemptDelivery::dispatch($delivery);

            return response()->json([
                'message' => 'Delivery replay initiated successfully',
                'data' => $delivery->fresh()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Delivery not found'
            ], 404);
        }
    }

    /**
     * Initiate bulk replay of deliveries.
     */
    public function bulkReplay(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'criteria' => 'required|array',
                'criteria.subscription_ids' => 'sometimes|array',
                'criteria.subscription_ids.*' => 'string|exists:subscriptions,id',
                'criteria.event_types' => 'sometimes|array',
                'criteria.event_types.*' => 'string|max:255',
                'criteria.statuses' => 'sometimes|array',
                'criteria.statuses.*' => 'string|in:failed,success',
                'criteria.date_from' => 'sometimes|date',
                'criteria.date_to' => 'sometimes|date|after_or_equal:criteria.date_from',
                'criteria.response_codes' => 'sometimes|array',
                'criteria.response_codes.*' => 'integer',
                'criteria.delivery_ids' => 'sometimes|array',
                'criteria.delivery_ids.*' => 'string',
            ]);

            // Add tenant filter to criteria
            $validated['criteria']['tenant_id'] = Auth::id();

            // Dispatch bulk replay job
            BulkReplayDeliveries::dispatch($validated['criteria'], Auth::id());

            return response()->json([
                'message' => 'Bulk replay initiated successfully. You will be notified when it completes.'
            ], 202);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Get replay statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'date',
            'date_to' => 'date|after_or_equal:date_from',
            'subscription_id' => 'string|exists:subscriptions,id',
        ]);

        $query = Delivery::whereHas('event', function ($q) {
            $q->forTenant(Auth::id());
        });

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('subscription_id')) {
            $query->forSubscription($request->subscription_id);
        }

        $stats = [
            'total_deliveries' => $query->count(),
            'by_status' => $query->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_response_code' => $query->whereNotNull('response_code')
                ->selectRaw('response_code, COUNT(*) as count')
                ->groupBy('response_code')
                ->orderBy('response_code')
                ->pluck('count', 'response_code'),
            'retry_distribution' => $query->selectRaw('attempt, COUNT(*) as count')
                ->groupBy('attempt')
                ->orderBy('attempt')
                ->pluck('count', 'attempt'),
            'avg_duration_ms' => $query->whereNotNull('duration_ms')
                ->avg('duration_ms'),
            'success_rate' => $query->count() > 0 
                ? round(($query->byStatus('success')->count() / $query->count()) * 100, 2)
                : 0,
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get delivery details for replay preview.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'criteria' => 'required|array',
            'criteria.subscription_ids' => 'sometimes|array',
            'criteria.subscription_ids.*' => 'string|exists:subscriptions,id',
            'criteria.event_types' => 'sometimes|array',
            'criteria.event_types.*' => 'string|max:255',
            'criteria.statuses' => 'sometimes|array',
            'criteria.statuses.*' => 'string|in:failed,success',
            'criteria.date_from' => 'sometimes|date',
            'criteria.date_to' => 'sometimes|date|after_or_equal:criteria.date_from',
            'criteria.response_codes' => 'sometimes|array',
            'criteria.response_codes.*' => 'integer',
            'criteria.delivery_ids' => 'sometimes|array',
            'criteria.delivery_ids.*' => 'string',
        ]);

        $query = Delivery::whereHas('event', function ($q) {
            $q->forTenant(Auth::id());
        });

        // Apply filters based on criteria
        $criteria = $validated['criteria'];

        if (!empty($criteria['subscription_ids'])) {
            $query->whereIn('subscription_id', $criteria['subscription_ids']);
        }

        if (!empty($criteria['event_types'])) {
            $query->whereHas('event', function ($q) use ($criteria) {
                $q->whereIn('event_type', $criteria['event_types']);
            });
        }

        if (!empty($criteria['statuses'])) {
            $query->whereIn('status', $criteria['statuses']);
        }

        if (!empty($criteria['date_from'])) {
            $query->whereDate('created_at', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->whereDate('created_at', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['response_codes'])) {
            $query->whereIn('response_code', $criteria['response_codes']);
        }

        if (!empty($criteria['delivery_ids'])) {
            $query->whereIn('id', $criteria['delivery_ids']);
        }

        $preview = [
            'total_count' => $query->count(),
            'by_status' => $query->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_subscription' => $query->join('subscriptions', 'deliveries.subscription_id', '=', 'subscriptions.id')
                ->selectRaw('subscriptions.name, COUNT(*) as count')
                ->groupBy('subscriptions.id', 'subscriptions.name')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'name'),
            'sample_deliveries' => $query->with([
                    'event:id,event_type,created_at',
                    'subscription:id,name,url'
                ])
                ->latest()
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'data' => $preview
        ]);
    }
}
