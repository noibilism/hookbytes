<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventDelivery;
use App\Models\Settings;
use App\Jobs\HandleFailedWebhookEvent;
use App\Services\WebhookTransformationService;
use App\Services\WebhookRoutingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ProcessWebhookEvent implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 5;
    public $backoff = [30, 120, 600, 1800, 3600]; // 30s, 2m, 10m, 30m, 1h
    public $maxExceptions = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Event $event
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->event->update([
                'status' => 'processing',
                'delivery_attempts' => $this->event->delivery_attempts + 1,
                'last_attempt_at' => now(),
            ]);

            $endpoint = $this->event->webhookEndpoint;

            // Apply transformations to the payload
            $transformationService = app(WebhookTransformationService::class);
            $transformedPayload = $transformationService->applyTransformations(
                $endpoint,
                $this->event->payload,
                $this->event->headers ?? []
            );

            // Apply routing rules
            $routingService = app(WebhookRoutingService::class);
            $routingResult = $routingService->evaluateRouting(
                $endpoint,
                $transformedPayload,
                $this->event->headers ?? []
            );

            // Handle drop action
            if ($routingResult['action'] === 'drop') {
                $this->event->update([
                    'status' => 'dropped',
                    'delivered_at' => now(),
                ]);

                Log::info('Event dropped by routing rule', [
                    'event_id' => $this->event->event_id,
                    'rule_name' => $routingResult['rule']->name ?? 'Unknown',
                ]);
                return;
            }

            // Get destinations from routing result
            $destinations = $routingResult['destinations'];
            if (empty($destinations)) {
                Log::warning('No destinations found for event', [
                    'event_id' => $this->event->event_id,
                ]);
                $this->event->update(['status' => 'failed']);
                return;
            }

            $allSuccessful = true;

            foreach ($destinations as $destination) {
                $url = is_array($destination) ? $destination['url'] : $destination;
                $success = $this->deliverToDestination($url, $transformedPayload);
                if (!$success) {
                    $allSuccessful = false;
                }
            }

            if ($allSuccessful) {
                $this->event->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);

                Log::info('Event delivered successfully', [
                    'event_id' => $this->event->event_id,
                    'destinations' => count($destinations),
                ]);
            } else {
                $this->event->update(['status' => 'failed']);
                $this->sendFailureNotifications();
                $this->fail('Some destinations failed');
            }

        } catch (\Exception $e) {
            $this->event->update(['status' => 'failed']);
            $this->sendFailureNotifications();
            
            Log::error('Event processing failed', [
                'event_id' => $this->event->event_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Deliver event to a specific destination
     */
    private function deliverToDestination(string $url, array $payload = null): bool
    {
        $startTime = microtime(true);
        $attemptNumber = $this->event->deliveries()->where('destination_url', $url)->count() + 1;

        try {
            $payloadToSend = $payload ?? $this->event->payload;
            $headers = $this->buildHeaders($payloadToSend);
            
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($url, $payloadToSend);

            $latency = (int) ((microtime(true) - $startTime) * 1000);
            $isSuccess = $response->successful();

            EventDelivery::create([
                'event_id' => $this->event->id,
                'destination_url' => $url,
                'attempt_number' => $attemptNumber,
                'status' => $isSuccess ? 'success' : 'failed',
                'response_code' => $response->status(),
                'response_body' => $response->body(),
                'response_headers' => $response->headers(),
                'latency_ms' => $latency,
                'attempted_at' => now(),
            ]);

            return $isSuccess;

        } catch (\Exception $e) {
            $latency = (int) ((microtime(true) - $startTime) * 1000);

            EventDelivery::create([
                'event_id' => $this->event->id,
                'destination_url' => $url,
                'attempt_number' => $attemptNumber,
                'status' => 'failed',
                'response_code' => null,
                'response_body' => null,
                'response_headers' => [],
                'latency_ms' => $latency,
                'error_message' => $e->getMessage(),
                'attempted_at' => now(),
            ]);

            return false;
        }
    }

    /**
     * Build headers for the webhook request
     */
    private function buildHeaders(array $payload = null): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Hookbytes-Webhook-Gateway/1.0',
            'X-Event-ID' => $this->event->event_id,
            'X-Event-Type' => $this->event->event_type,
            'X-Delivery-Attempt' => (string) ($this->event->delivery_attempts + 1),
            'X-Timestamp' => $this->event->created_at->toISOString(),
        ];

        // Add custom headers from endpoint configuration
        $endpoint = $this->event->webhookEndpoint;
        if ($endpoint->headers_config) {
            $headers = array_merge($headers, $endpoint->headers_config);
        }

        // Add HMAC signature if configured
        if ($endpoint->auth_method === 'hmac' && $endpoint->auth_secret) {
            $payloadForSignature = json_encode($payload ?? $this->event->payload);
            $signature = 'sha256=' . hash_hmac('sha256', $payloadForSignature, $endpoint->auth_secret);
            $headers['X-Signature-256'] = $signature;
        }

        return $headers;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Update event status to failed
        $this->event->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        Log::error('Webhook event processing failed permanently', [
            'event_id' => $this->event->event_id,
            'project_id' => $this->event->project_id,
            'endpoint_id' => $this->event->webhook_endpoint_id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Send failure notifications
        $this->sendFailureNotifications();

        // Dispatch to dead-letter queue for manual review
        HandleFailedWebhookEvent::dispatch(
            $this->event,
            $exception->getMessage(),
            $this->attempts()
        );
    }

    /**
     * Send failure notifications via Slack and/or email
     */
    private function sendFailureNotifications(): void
    {
        try {
            $settings = Settings::current();
            
            // Send Slack notification
            if ($settings->slack_notifications_enabled && $settings->slack_webhook_url) {
                $this->sendSlackNotification($settings->slack_webhook_url);
            }
            
            // Send email notification
            if ($settings->email_notifications_enabled && $settings->notification_email) {
                $this->sendEmailNotification($settings->notification_email);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send failure notifications', [
                'event_id' => $this->event->event_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Slack notification
     */
    private function sendSlackNotification(string $webhookUrl): void
    {
        $project = $this->event->project;
        $endpoint = $this->event->webhookEndpoint;
        
        $message = [
            'text' => '🚨 Webhook Event Failed',
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        [
                            'title' => 'Event ID',
                            'value' => $this->event->event_id,
                            'short' => true
                        ],
                        [
                            'title' => 'Project',
                            'value' => $project->name,
                            'short' => true
                        ],
                        [
                            'title' => 'Endpoint',
                            'value' => $endpoint->name,
                            'short' => true
                        ],
                        [
                            'title' => 'Attempts',
                            'value' => $this->event->delivery_attempts,
                            'short' => true
                        ],
                        [
                            'title' => 'Failed At',
                            'value' => now()->format('Y-m-d H:i:s'),
                            'short' => false
                        ]
                    ]
                ]
            ]
        ];
        
        Http::timeout(10)->post($webhookUrl, $message);
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(string $email): void
    {
        $project = $this->event->project;
        $endpoint = $this->event->webhookEndpoint;
        
        $subject = 'Webhook Event Failed - ' . $project->name;
        $body = "A webhook event has failed delivery:\n\n";
        $body .= "Event ID: {$this->event->event_id}\n";
        $body .= "Project: {$project->name}\n";
        $body .= "Endpoint: {$endpoint->name}\n";
        $body .= "Delivery Attempts: {$this->event->delivery_attempts}\n";
        $body .= "Failed At: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $body .= "Please check the dashboard for more details.";
        
        Mail::raw($body, function ($message) use ($email, $subject) {
            $message->to($email)
                   ->subject($subject);
        });
    }
}
