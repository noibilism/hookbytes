<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class BulkReplayDeliveries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Batch size for processing deliveries
     */
    const BATCH_SIZE = 100;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $criteria,
        public ?string $userId = null
    ) {
        $this->onQueue('webhook-replays');
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        Log::info('Starting bulk replay deliveries', [
            'criteria' => $this->criteria,
            'user_id' => $this->userId
        ]);

        try {
            $totalReplayed = 0;
            $query = $this->buildQuery();
            
            // Get total count for logging
            $totalCount = $query->count();
            
            Log::info('Found deliveries for replay', [
                'total_count' => $totalCount,
                'criteria' => $this->criteria
            ]);

            if ($totalCount === 0) {
                Log::info('No deliveries found matching criteria');
                return;
            }

            // Process in batches to avoid memory issues
            $query->chunk(self::BATCH_SIZE, function (Collection $deliveries) use (&$totalReplayed) {
                foreach ($deliveries as $delivery) {
                    $this->replayDelivery($delivery);
                    $totalReplayed++;
                }

                Log::debug('Processed batch for replay', [
                    'batch_size' => $deliveries->count(),
                    'total_replayed' => $totalReplayed
                ]);
            });

            Log::info('Bulk replay completed', [
                'total_replayed' => $totalReplayed,
                'criteria' => $this->criteria
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk replay failed', [
                'error' => $e->getMessage(),
                'criteria' => $this->criteria,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Build query based on criteria
     */
    private function buildQuery()
    {
        $query = Delivery::query();

        // Filter by tenant
        if (isset($this->criteria['tenant_id'])) {
            $query->whereHas('event', function ($q) {
                $q->where('tenant_id', $this->criteria['tenant_id']);
            });
        }

        // Filter by subscription
        if (isset($this->criteria['subscription_id'])) {
            $query->where('subscription_id', $this->criteria['subscription_id']);
        }

        // Filter by event type
        if (isset($this->criteria['event_type'])) {
            $query->whereHas('event', function ($q) {
                $q->where('event_type', $this->criteria['event_type']);
            });
        }

        // Filter by delivery status
        if (isset($this->criteria['status'])) {
            if (is_array($this->criteria['status'])) {
                $query->whereIn('status', $this->criteria['status']);
            } else {
                $query->where('status', $this->criteria['status']);
            }
        }

        // Filter by date range
        if (isset($this->criteria['date_from'])) {
            $query->where('created_at', '>=', $this->criteria['date_from']);
        }

        if (isset($this->criteria['date_to'])) {
            $query->where('created_at', '<=', $this->criteria['date_to']);
        }

        // Filter by response code
        if (isset($this->criteria['response_code'])) {
            $query->where('response_code', $this->criteria['response_code']);
        }

        // Filter by specific delivery IDs
        if (isset($this->criteria['delivery_ids']) && is_array($this->criteria['delivery_ids'])) {
            $query->whereIn('id', $this->criteria['delivery_ids']);
        }

        // Only include deliveries that can be replayed
        $query->whereIn('status', ['failed', 'permanently_failed', 'success']);

        return $query->with(['event', 'subscription']);
    }

    /**
     * Replay a single delivery
     */
    private function replayDelivery(Delivery $delivery): void
    {
        try {
            // Reset delivery for retry
            $delivery->update([
                'status' => 'pending',
                'attempt' => 1,
                'next_retry_at' => now(),
                'response_code' => null,
                'response_body' => null,
                'error_message' => null,
                'duration_ms' => null,
                'signature' => null
            ]);

            // Queue the delivery attempt
            AttemptDelivery::dispatch($delivery);

            Log::debug('Delivery queued for replay', [
                'delivery_id' => $delivery->id,
                'event_id' => $delivery->event_id,
                'subscription_id' => $delivery->subscription_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to replay delivery', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage()
            ]);
            
            // Continue with other deliveries even if one fails
        }
    }

    /**
     * Get replay statistics
     */
    public function getReplayStats(): array
    {
        $query = $this->buildQuery();
        
        return [
            'total_deliveries' => $query->count(),
            'failed_deliveries' => $query->where('status', 'failed')->count(),
            'permanently_failed_deliveries' => $query->where('status', 'permanently_failed')->count(),
            'successful_deliveries' => $query->where('status', 'success')->count(),
            'criteria' => $this->criteria
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkReplayDeliveries job failed', [
            'criteria' => $this->criteria,
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);
    }
}
