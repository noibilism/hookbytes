<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Delivery;
use App\Services\WebhookService;
use App\Services\ThrottleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchEventDeliveries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Event $event
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService, ThrottleService $throttleService): void
    {
        Log::info('Dispatching event deliveries', [
            'event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'tenant_id' => $this->event->tenant_id
        ]);

        try {
            // Create delivery records for all matching subscriptions
            $deliveriesCreated = $webhookService->createDeliveries($this->event);

            if ($deliveriesCreated === 0) {
                Log::info('No deliveries created for event', [
                    'event_id' => $this->event->id
                ]);
                return;
            }

            // Queue individual delivery attempts
            $deliveries = Delivery::where('event_id', $this->event->id)
                ->where('status', 'pending')
                ->with('subscription')
                ->get();

            foreach ($deliveries as $delivery) {
                $subscription = $delivery->subscription;
                
                // Check if subscription is throttled
                [$shouldDelay, $delaySeconds] = $throttleService->shouldDelay($subscription);
                
                if ($shouldDelay) {
                    Log::info('Delaying delivery due to throttling', [
                        'delivery_id' => $delivery->id,
                        'subscription_id' => $subscription->id,
                        'delay_seconds' => $delaySeconds
                    ]);
                    
                    // Update next retry time
                    $delivery->update([
                        'next_retry_at' => now()->addSeconds($delaySeconds)
                    ]);
                    
                    // Queue with delay
                    AttemptDelivery::dispatch($delivery)->delay(now()->addSeconds($delaySeconds));
                } else {
                    // Queue immediately
                    AttemptDelivery::dispatch($delivery);
                }
            }

            Log::info('Event deliveries dispatched', [
                'event_id' => $this->event->id,
                'deliveries_count' => $deliveriesCreated
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch event deliveries', [
                'event_id' => $this->event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update event status to failed
            $this->event->update(['status' => 'failed']);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('DispatchEventDeliveries job failed', [
            'event_id' => $this->event->id,
            'error' => $exception->getMessage()
        ]);

        $this->event->update(['status' => 'failed']);
    }
}
