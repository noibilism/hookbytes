<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HookController extends Controller
{
    /**
     * Display a listing of webhook subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'event_type' => 'string|max:255',
            'active' => 'boolean',
            'search' => 'string|max:255',
        ]);

        $query = Subscription::forTenant(Auth::id())
            ->with(['deliveryMetrics' => function ($query) {
                $query->latest('date')->limit(7);
            }]);

        if ($request->filled('event_type')) {
            $query->forEventType($request->event_type);
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($subscriptions);
    }

    /**
     * Store a newly created webhook subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'description' => 'nullable|string|max:1000',
            'event_types' => 'required|array|min:1',
            'event_types.*' => 'required|string|max:255',
            'secret' => 'nullable|string|min:8|max:255',
            'headers' => 'nullable|array',
            'headers.*' => 'string|max:1000',
            'active' => 'boolean',
            'rate_limit_per_minute' => 'integer|min:1|max:1000',
            'max_retries' => 'integer|min:0|max:10',
        ]);

        $validated['tenant_id'] = Auth::id();
        $validated['active'] = $validated['active'] ?? true;
        $validated['rate_limit_per_minute'] = $validated['rate_limit_per_minute'] ?? 60;
        $validated['max_retries'] = $validated['max_retries'] ?? 3;

        $subscription = Subscription::create($validated);

        return response()->json([
            'message' => 'Webhook subscription created successfully',
            'data' => $subscription->load('deliveryMetrics')
        ], 201);
    }

    /**
     * Display the specified webhook subscription.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $subscription = Subscription::forTenant(Auth::id())
                ->with([
                    'deliveryMetrics' => function ($query) {
                        $query->latest('date')->limit(30);
                    },
                    'deliveries' => function ($query) {
                        $query->latest()->limit(10);
                    }
                ])
                ->findOrFail($id);

            return response()->json([
                'data' => $subscription
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Webhook subscription not found'
            ], 404);
        }
    }

    /**
     * Update the specified webhook subscription.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $subscription = Subscription::forTenant(Auth::id())->findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'url' => 'sometimes|required|url|max:500',
                'description' => 'nullable|string|max:1000',
                'event_types' => 'sometimes|required|array|min:1',
                'event_types.*' => 'required|string|max:255',
                'secret' => 'nullable|string|min:8|max:255',
                'headers' => 'nullable|array',
                'headers.*' => 'string|max:1000',
                'active' => 'boolean',
                'rate_limit_per_minute' => 'integer|min:1|max:1000',
                'max_retries' => 'integer|min:0|max:10',
            ]);

            $subscription->update($validated);

            return response()->json([
                'message' => 'Webhook subscription updated successfully',
                'data' => $subscription->fresh()->load('deliveryMetrics')
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Webhook subscription not found'
            ], 404);
        }
    }

    /**
     * Remove the specified webhook subscription.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $subscription = Subscription::forTenant(Auth::id())->findOrFail($id);
            $subscription->delete();

            return response()->json([
                'message' => 'Webhook subscription deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Webhook subscription not found'
            ], 404);
        }
    }

    /**
     * Toggle the active status of a webhook subscription.
     */
    public function toggle(string $id): JsonResponse
    {
        try {
            $subscription = Subscription::forTenant(Auth::id())->findOrFail($id);
            $subscription->update(['active' => !$subscription->active]);

            return response()->json([
                'message' => 'Webhook subscription status updated successfully',
                'data' => $subscription->fresh()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Webhook subscription not found'
            ], 404);
        }
    }

    /**
     * Test a webhook subscription by sending a test event.
     */
    public function test(string $id): JsonResponse
    {
        try {
            $subscription = Subscription::forTenant(Auth::id())
                ->active()
                ->findOrFail($id);

            // Create a test event
            $testPayload = [
                'event_type' => 'webhook.test',
                'timestamp' => now()->toISOString(),
                'data' => [
                    'message' => 'This is a test webhook delivery',
                    'subscription_id' => $subscription->id,
                    'subscription_name' => $subscription->name,
                ]
            ];

            // Use WebhookService to dispatch the test event
            app(\App\Services\WebhookService::class)->createEvent(
                'webhook.test',
                $testPayload,
                Auth::id(),
                'test-' . uniqid()
            );

            return response()->json([
                'message' => 'Test webhook sent successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Active webhook subscription not found'
            ], 404);
        }
    }
}
