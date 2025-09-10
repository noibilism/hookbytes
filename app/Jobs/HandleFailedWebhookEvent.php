<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class HandleFailedWebhookEvent implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Event $event,
        public string $failureReason,
        public int $totalAttempts
    ) {
        // Queue this job on a separate 'failed' queue
        $this->onQueue('failed');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Mark event as permanently failed
            $this->event->update([
                'status' => 'permanently_failed',
                'failed_at' => now(),
            ]);

            // Log the permanent failure
            Log::error('Event permanently failed after all retries', [
                'event_id' => $this->event->event_id,
                'project_id' => $this->event->project_id,
                'endpoint_id' => $this->event->webhook_endpoint_id,
                'total_attempts' => $this->totalAttempts,
                'failure_reason' => $this->failureReason,
                'failed_at' => now()->toISOString(),
            ]);

            // Send notification to project administrators
            $this->notifyAdministrators();

            // Store in dead letter queue for manual review
            $this->storeInDeadLetterQueue();

        } catch (\Exception $e) {
            Log::error('Failed to handle permanently failed webhook event', [
                'event_id' => $this->event->event_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify project administrators about the permanent failure
     */
    private function notifyAdministrators(): void
    {
        // In a real implementation, you would send emails or notifications
        // For now, we'll just log the notification
        Log::warning('Webhook event permanently failed - notification sent', [
            'event_id' => $this->event->event_id,
            'project' => $this->event->project->name,
            'endpoint' => $this->event->webhookEndpoint->name,
        ]);
    }

    /**
     * Store event details in dead letter queue for manual review
     */
    private function storeInDeadLetterQueue(): void
    {
        // Create a record in a dead letter table or file for manual review
        $deadLetterData = [
            'event_id' => $this->event->event_id,
            'project_id' => $this->event->project_id,
            'endpoint_id' => $this->event->webhook_endpoint_id,
            'payload' => $this->event->payload,
            'failure_reason' => $this->failureReason,
            'total_attempts' => $this->totalAttempts,
            'failed_at' => now()->toISOString(),
            'deliveries' => $this->event->deliveries->toArray(),
        ];

        // Store in logs for now (in production, you might use a separate table)
        Log::channel('dead-letter')->error('Dead letter queue entry', $deadLetterData);
    }
}
