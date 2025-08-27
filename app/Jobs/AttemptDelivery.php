<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\DeadLetter;
use App\Services\WebhookService;
use App\Services\ThrottleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AttemptDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1; // We handle retries manually

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Delivery $delivery
    ) {
        $this->onQueue('webhook-deliveries');
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService, ThrottleService $throttleService): void
    {
        // Skip if delivery is no longer pending
        if ($this->delivery->status !== 'pending') {
            Log::debug('Skipping delivery - not pending', [
                'delivery_id' => $this->delivery->id,
                'status' => $this->delivery->status
            ]);
            return;
        }

        $subscription = $this->delivery->subscription;
        $event = $this->delivery->event;

        Log::info('Attempting webhook delivery', [
            'delivery_id' => $this->delivery->id,
            'event_id' => $event->id,
            'subscription_id' => $subscription->id,
            'attempt' => $this->delivery->attempt,
            'endpoint' => $subscription->endpoint_url
        ]);

        // Check throttling
        if ($throttleService->isThrottled($subscription)) {
            Log::info('Delivery throttled, rescheduling', [
                'delivery_id' => $this->delivery->id,
                'subscription_id' => $subscription->id
            ]);
            
            $this->rescheduleDelivery($throttleService->getTimeUntilReset($subscription->id));
            return;
        }

        $startTime = microtime(true);
        $requestId = Str::ulid()->toBase32();

        try {
            // Increment throttle counter
            $throttleService->increment($subscription);

            // Prepare payload and signature
            $payload = $webhookService->preparePayload($event, $subscription);
            $signature = $webhookService->generateSignature($payload, $subscription);
            $headers = $webhookService->getDeliveryHeaders($subscription, $signature, $requestId);

            // Make HTTP request
            $response = Http::withHeaders($headers)
                ->timeout(config('webhooks.delivery_timeout', 15))
                ->retry(1, 100) // Single retry with 100ms delay
                ->post($subscription->endpoint_url, $payload);

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $responseCode = $response->status();
            $responseBody = $response->body();

            // Update delivery record
            $this->delivery->update([
                'status' => $this->isSuccessfulResponse($responseCode) ? 'success' : 'failed',
                'response_code' => $responseCode,
                'response_body' => $this->truncateResponseBody($responseBody),
                'duration_ms' => $duration,
                'signature' => $signature
            ]);

            if ($this->isSuccessfulResponse($responseCode)) {
                Log::info('Webhook delivery successful', [
                    'delivery_id' => $this->delivery->id,
                    'response_code' => $responseCode,
                    'duration_ms' => $duration
                ]);
            } else {
                Log::warning('Webhook delivery failed with HTTP error', [
                    'delivery_id' => $this->delivery->id,
                    'response_code' => $responseCode,
                    'duration_ms' => $duration
                ]);
                
                $this->handleFailedDelivery("HTTP {$responseCode}: {$responseBody}");
            }

        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            
            Log::error('Webhook delivery exception', [
                'delivery_id' => $this->delivery->id,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);

            $this->delivery->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_ms' => $duration
            ]);

            $this->handleFailedDelivery($e->getMessage());
        }
    }

    /**
     * Handle failed delivery with retry logic
     */
    private function handleFailedDelivery(string $errorMessage): void
    {
        $subscription = $this->delivery->subscription;
        $maxRetries = $subscription->max_retries ?? config('webhooks.max_retries', 3);

        if ($this->delivery->attempt < $maxRetries) {
            // Calculate exponential backoff delay
            $delay = $this->calculateRetryDelay($this->delivery->attempt);
            
            Log::info('Scheduling delivery retry', [
                'delivery_id' => $this->delivery->id,
                'attempt' => $this->delivery->attempt,
                'max_retries' => $maxRetries,
                'delay_seconds' => $delay
            ]);

            $this->delivery->update([
                'status' => 'pending',
                'attempt' => $this->delivery->attempt + 1,
                'next_retry_at' => now()->addSeconds($delay)
            ]);

            // Queue retry
            self::dispatch($this->delivery)->delay(now()->addSeconds($delay));
        } else {
            Log::error('Delivery permanently failed after max retries', [
                'delivery_id' => $this->delivery->id,
                'max_retries' => $maxRetries
            ]);

            $this->delivery->update(['status' => 'permanently_failed']);
            
            // Create dead letter record
            DeadLetter::create([
                'delivery_id' => $this->delivery->id,
                'reason' => 'max_retries_exceeded',
                'dump' => [
                    'error_message' => $errorMessage,
                    'attempts' => $this->delivery->attempt,
                    'max_retries' => $maxRetries,
                    'last_response_code' => $this->delivery->response_code,
                    'subscription_endpoint' => $this->delivery->subscription->endpoint_url
                ],
                'created_at' => now()
            ]);
        }
    }

    /**
     * Reschedule delivery due to throttling
     */
    private function rescheduleDelivery(int $delaySeconds): void
    {
        $this->delivery->update([
            'next_retry_at' => now()->addSeconds($delaySeconds)
        ]);

        self::dispatch($this->delivery)->delay(now()->addSeconds($delaySeconds));
    }

    /**
     * Calculate retry delay using exponential backoff
     */
    private function calculateRetryDelay(int $attempt): int
    {
        $baseDelay = config('webhooks.retry_base_delay', 60); // 1 minute
        $maxDelay = config('webhooks.retry_max_delay', 3600); // 1 hour
        
        $delay = $baseDelay * pow(2, $attempt - 1);
        
        return min($delay, $maxDelay);
    }

    /**
     * Check if response code indicates success
     */
    private function isSuccessfulResponse(int $responseCode): bool
    {
        return $responseCode >= 200 && $responseCode < 300;
    }

    /**
     * Truncate response body to prevent database overflow
     */
    private function truncateResponseBody(string $responseBody): string
    {
        $maxLength = config('webhooks.max_response_body_length', 10000);
        
        if (strlen($responseBody) > $maxLength) {
            return substr($responseBody, 0, $maxLength) . '... [truncated]';
        }
        
        return $responseBody;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AttemptDelivery job failed', [
            'delivery_id' => $this->delivery->id,
            'error' => $exception->getMessage()
        ]);

        $this->delivery->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
    }
}
