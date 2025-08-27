<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Subscription;
use App\Models\Delivery;
use App\Jobs\DispatchEventDeliveries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WebhookService
{
    public function __construct(
        private IdempotencyService $idempotencyService,
        private RedactionService $redactionService,
        private SignatureService $signatureService
    ) {}

    /**
     * Create and dispatch a new webhook event
     *
     * @param array $data
     * @return Event
     */
    public function createEvent(array $data): Event
    {
        $this->validateEventData($data);

        $tenantId = $data['tenant_id'];
        $idempotencyKey = $data['idempotency_key'] ?? $this->idempotencyService->generateKey();

        // Check for duplicate events
        if ($this->idempotencyService->exists($idempotencyKey, $tenantId)) {
            $existingEvent = $this->idempotencyService->getExistingEvent($idempotencyKey, $tenantId);
            if ($existingEvent) {
                Log::info('Duplicate event detected', [
                    'tenant_id' => $tenantId,
                    'idempotency_key' => $idempotencyKey,
                    'existing_event_id' => $existingEvent->id
                ]);
                return $existingEvent;
            }
        }

        return DB::transaction(function () use ($data, $idempotencyKey, $tenantId) {
            // Create the event
            $event = Event::create([
                'tenant_id' => $tenantId,
                'event_type' => $data['event_type'],
                'payload' => $data['payload'],
                'source' => $data['source'] ?? 'api',
                'idempotency_key' => $idempotencyKey,
                'status' => 'pending'
            ]);

            // Store idempotency key
            $this->idempotencyService->store($idempotencyKey, $tenantId, $event->id);

            // Dispatch deliveries asynchronously
            DispatchEventDeliveries::dispatch($event);

            Log::info('Webhook event created', [
                'event_id' => $event->id,
                'tenant_id' => $tenantId,
                'event_type' => $event->event_type,
                'idempotency_key' => $idempotencyKey
            ]);

            return $event;
        });
    }

    /**
     * Get active subscriptions for an event
     *
     * @param Event $event
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubscriptionsForEvent(Event $event)
    {
        return Subscription::active()
            ->forTenant($event->tenant_id)
            ->forEventType($event->event_type)
            ->get();
    }

    /**
     * Create delivery records for an event
     *
     * @param Event $event
     * @return int Number of deliveries created
     */
    public function createDeliveries(Event $event): int
    {
        $subscriptions = $this->getSubscriptionsForEvent($event);
        
        if ($subscriptions->isEmpty()) {
            Log::info('No active subscriptions found for event', [
                'event_id' => $event->id,
                'event_type' => $event->event_type,
                'tenant_id' => $event->tenant_id
            ]);
            
            $event->update(['status' => 'no_subscribers']);
            return 0;
        }

        $deliveriesCreated = 0;
        
        foreach ($subscriptions as $subscription) {
            $delivery = Delivery::create([
                'event_id' => $event->id,
                'subscription_id' => $subscription->id,
                'attempt' => 1,
                'status' => 'pending',
                'next_retry_at' => now()
            ]);
            
            $deliveriesCreated++;
            
            Log::debug('Delivery created', [
                'delivery_id' => $delivery->id,
                'event_id' => $event->id,
                'subscription_id' => $subscription->id
            ]);
        }

        $event->update(['status' => 'processing']);
        
        Log::info('Deliveries created for event', [
            'event_id' => $event->id,
            'deliveries_count' => $deliveriesCreated
        ]);

        return $deliveriesCreated;
    }

    /**
     * Prepare webhook payload for delivery
     *
     * @param Event $event
     * @param Subscription $subscription
     * @return array
     */
    public function preparePayload(Event $event, Subscription $subscription): array
    {
        $payload = [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'tenant_id' => $event->tenant_id,
            'source' => $event->source,
            'timestamp' => $event->created_at->toISOString(),
            'data' => $event->payload
        ];

        // Apply redaction if configured
        if (config('webhooks.enable_redaction', false)) {
            $payload['data'] = $this->redactionService->redact($payload['data']);
        }

        return $payload;
    }

    /**
     * Generate signature for webhook payload
     *
     * @param array $payload
     * @param Subscription $subscription
     * @return string
     */
    public function generateSignature(array $payload, Subscription $subscription): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        return $this->signatureService->generateSignature(
            $payloadJson,
            $subscription->secret,
            $subscription->signature_algo
        );
    }

    /**
     * Get delivery headers for webhook request
     *
     * @param Subscription $subscription
     * @param string $signature
     * @param string $requestId
     * @return array
     */
    public function getDeliveryHeaders(Subscription $subscription, string $signature, string $requestId): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel-Webhook-Service/1.0',
            'X-Webhook-Signature-256' => 'sha256=' . $signature,
            'X-Webhook-Delivery' => $requestId,
            'X-Webhook-Timestamp' => now()->timestamp
        ];

        // Add custom headers from subscription
        if ($subscription->headers) {
            $headers = array_merge($headers, $subscription->headers);
        }

        return $headers;
    }

    /**
     * Replay deliveries for specific criteria
     *
     * @param array $criteria
     * @return int Number of deliveries queued for replay
     */
    public function replayDeliveries(array $criteria): int
    {
        $query = Delivery::query();

        if (isset($criteria['tenant_id'])) {
            $query->whereHas('event', function ($q) use ($criteria) {
                $q->where('tenant_id', $criteria['tenant_id']);
            });
        }

        if (isset($criteria['subscription_id'])) {
            $query->where('subscription_id', $criteria['subscription_id']);
        }

        if (isset($criteria['event_type'])) {
            $query->whereHas('event', function ($q) use ($criteria) {
                $q->where('event_type', $criteria['event_type']);
            });
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }

        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }

        $deliveries = $query->get();
        
        foreach ($deliveries as $delivery) {
            // Reset delivery for retry
            $delivery->update([
                'status' => 'pending',
                'attempt' => 1,
                'next_retry_at' => now(),
                'response_code' => null,
                'response_body' => null,
                'error_message' => null,
                'duration_ms' => null
            ]);
        }

        Log::info('Deliveries queued for replay', [
            'count' => $deliveries->count(),
            'criteria' => $criteria
        ]);

        return $deliveries->count();
    }

    /**
     * Validate event data
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validateEventData(array $data): void
    {
        $required = ['tenant_id', 'event_type', 'payload'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!is_array($data['payload'])) {
            throw new InvalidArgumentException('Payload must be an array');
        }

        if (isset($data['idempotency_key']) && !$this->idempotencyService->isValidKey($data['idempotency_key'])) {
            throw new InvalidArgumentException('Invalid idempotency key format');
        }
    }
}