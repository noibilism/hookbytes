<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $webhookEndpoint;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['is_active' => true]);
        $this->webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function handles_nonexistent_webhook_endpoints_gracefully()
    {
        // Test with completely invalid URL path
        $response = $this->postJson('/api/webhook/nonexistent-endpoint', [
            'test' => 'data'
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Webhook endpoint not found']);

        // Test with invalid short URL
        $response = $this->postJson('/api/w/invalid1', [
            'test' => 'data'
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Webhook endpoint not found']);

        // Test info endpoint for nonexistent webhook
        $response = $this->getJson('/api/webhook/nonexistent/info');
        $response->assertStatus(404);

        $response = $this->getJson('/api/w/invalid1/info');
        $response->assertStatus(404);
    }

    /** @test */
    public function handles_inactive_webhook_endpoints()
    {
        // Deactivate the webhook endpoint
        $this->webhookEndpoint->update(['is_active' => false]);

        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'test' => 'data'
        ]);

        $response->assertStatus(410) // Gone
            ->assertJson(['error' => 'Webhook endpoint is inactive']);

        // Test with short URL
        $response = $this->postJson('/api/w/' . $this->webhookEndpoint->short_url, [
            'test' => 'data'
        ]);

        $response->assertStatus(410);
    }

    /** @test */
    public function handles_inactive_projects()
    {
        // Deactivate the project
        $this->project->update(['is_active' => false]);

        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'test' => 'data'
        ]);

        $response->assertStatus(410)
            ->assertJson(['error' => 'Project is inactive']);
    }

    /** @test */
    public function handles_malformed_json_payloads()
    {
        // Send malformed JSON
        $response = $this->call('POST', '/api/webhook/' . $this->webhookEndpoint->url_path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"invalid": json}');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid JSON payload']);

        // Send empty payload
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, []);
        $response->assertStatus(400)
            ->assertJson(['error' => 'Empty payload not allowed']);

        // Send null payload
        $response = $this->call('POST', '/api/webhook/' . $this->webhookEndpoint->url_path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'null');

        $response->assertStatus(400);
    }

    /** @test */
    public function handles_oversized_payloads()
    {
        // Create a very large payload
        $largeData = str_repeat('A', 5 * 1024 * 1024); // 5MB
        
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'large_field' => $largeData
        ]);

        // Should reject with appropriate error
        $this->assertContains($response->getStatusCode(), [413, 400, 422]);
    }

    /** @test */
    public function handles_database_connection_failures()
    {
        // Simulate database connection failure
        DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database connection failed'));
        
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'test' => 'data'
        ]);

        $response->assertStatus(500)
            ->assertJson(['error' => 'Internal server error']);
    }

    /** @test */
    public function handles_queue_failures_gracefully()
    {
        Queue::fake();
        Queue::shouldReceive('push')->andThrow(new \Exception('Queue connection failed'));

        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'test' => 'data'
        ]);

        // Should still accept the webhook but handle queue failure
        $response->assertStatus(200);
        
        // Event should still be created even if queue fails
        $this->assertDatabaseHas('events', [
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function handles_webhook_delivery_failures()
    {
        Queue::fake();
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Server error'], 500)
        ]);

        // Create event and process it
        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['test' => 'data'],
            'status' => 'pending',
        ]);

        // Process the webhook event job
        $job = new ProcessWebhookEvent($event);
        $job->handle();

        // Event should be marked as failed
        $event->refresh();
        $this->assertEquals('failed', $event->status);
        $this->assertGreaterThan(0, $event->attempt_count);
    }

    /** @test */
    public function handles_webhook_timeout_scenarios()
    {
        Queue::fake();
        Http::fake([
            'example.com/*' => function () {
                // Simulate timeout by throwing exception
                throw new \Exception('Connection timeout');
            }
        ]);

        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['test' => 'data'],
            'status' => 'pending',
        ]);

        $job = new ProcessWebhookEvent($event);
        $job->handle();

        $event->refresh();
        $this->assertEquals('failed', $event->status);
    }

    /** @test */
    public function handles_invalid_destination_urls_during_delivery()
    {
        // Create endpoint with invalid URL that passes validation but fails at runtime
        $endpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://nonexistent-domain-12345.com/webhook'],
            'auth_method' => 'none',
        ]);

        Queue::fake();
        Http::fake([
            'nonexistent-domain-12345.com/*' => function () {
                throw new \Exception('DNS resolution failed');
            }
        ]);

        $event = Event::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['test' => 'data'],
        ]);

        $job = new ProcessWebhookEvent($event);
        $job->handle();

        $event->refresh();
        $this->assertEquals('failed', $event->status);
    }

    /** @test */
    public function handles_authentication_failures_during_delivery()
    {
        $hmacEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://secure.example.com/webhook'],
            'auth_method' => 'hmac',
            'auth_secret' => 'secret-key',
        ]);

        Queue::fake();
        Http::fake([
            'secure.example.com/*' => Http::response(['error' => 'Invalid signature'], 401)
        ]);

        $event = Event::factory()->create([
            'webhook_endpoint_id' => $hmacEndpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['test' => 'data'],
        ]);

        $job = new ProcessWebhookEvent($event);
        $job->handle();

        $event->refresh();
        $this->assertEquals('failed', $event->status);
    }

    /** @test */
    public function handles_concurrent_webhook_processing()
    {
        // Create multiple events for the same endpoint
        $events = Event::factory()->count(10)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        Queue::fake();
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200)
        ]);

        // Process all events concurrently (simulate)
        foreach ($events as $event) {
            $job = new ProcessWebhookEvent($event);
            $job->handle();
        }

        // All events should be processed successfully
        foreach ($events as $event) {
            $event->refresh();
            $this->assertEquals('delivered', $event->status);
        }
    }

    /** @test */
    public function handles_retry_exhaustion()
    {
        $retryEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://failing.example.com/webhook'],
            'retry_config' => [
                'max_attempts' => 3,
                'retry_delay' => 1,
            ],
        ]);

        Queue::fake();
        Http::fake([
            'failing.example.com/*' => Http::response(['error' => 'Always fails'], 500)
        ]);

        $event = Event::factory()->create([
            'webhook_endpoint_id' => $retryEndpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['test' => 'data'],
            'attempt_count' => 0,
        ]);

        // Simulate multiple retry attempts
        for ($i = 0; $i < 4; $i++) {
            $job = new ProcessWebhookEvent($event);
            $job->handle();
            $event->refresh();
        }

        // Should be permanently failed after max attempts
        $this->assertEquals('failed', $event->status);
        $this->assertEquals(3, $event->attempt_count);
    }

    /** @test */
    public function handles_api_validation_edge_cases()
    {
        // Test extremely long endpoint names
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => str_repeat('A', 1000),
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
        ], [
            'Authorization' => 'Bearer ' . $this->project->api_key
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test too many destination URLs
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Too Many URLs',
            'destination_urls' => array_fill(0, 100, 'https://example.com/webhook'),
            'auth_method' => 'none',
        ], [
            'Authorization' => 'Bearer ' . $this->project->api_key
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination_urls']);

        // Test invalid auth method
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Invalid Auth',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'invalid_method',
        ], [
            'Authorization' => 'Bearer ' . $this->project->api_key
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auth_method']);
    }

    /** @test */
    public function handles_dashboard_form_validation_errors()
    {
        // Test missing required fields
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', [
                '_token' => 'test-token',
            ]);

        $response->assertSessionHasErrors(['name', 'destination_urls']);

        // Test invalid retry configuration
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', [
                'name' => 'Test Endpoint',
                'destination_urls' => ['https://example.com/webhook'],
                'auth_method' => 'none',
                'retry_config' => [
                    'max_attempts' => -1, // Invalid negative value
                    'retry_delay' => 'invalid', // Invalid non-numeric value
                ],
                '_token' => 'test-token',
            ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function handles_event_replay_edge_cases()
    {
        // Try to replay non-existent event
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/99999/replay', [
                '_token' => 'test-token'
            ]);

        $response->assertStatus(404);

        // Try to replay event from inactive endpoint
        $inactiveEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => false,
        ]);

        $event = Event::factory()->create([
            'webhook_endpoint_id' => $inactiveEndpoint->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/' . $event->id . '/replay', [
                '_token' => 'test-token'
            ]);

        $response->assertStatus(400); // Bad request due to inactive endpoint
    }

    /** @test */
    public function handles_bulk_operations_edge_cases()
    {
        // Test bulk retry with empty event list
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/bulk-retry', [
                'event_ids' => [],
                '_token' => 'test-token'
            ]);

        $response->assertSessionHasErrors(['event_ids']);

        // Test bulk retry with non-existent events
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/bulk-retry', [
                'event_ids' => [99999, 99998, 99997],
                '_token' => 'test-token'
            ]);

        $response->assertRedirect(); // Should handle gracefully

        // Test bulk retry with mixed valid/invalid events
        $validEvent = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/bulk-retry', [
                'event_ids' => [$validEvent->id, 99999],
                '_token' => 'test-token'
            ]);

        $response->assertRedirect(); // Should process valid events and skip invalid ones
    }

    /** @test */
    public function handles_project_deletion_edge_cases()
    {
        // Create project with endpoints and events
        $projectToDelete = Project::factory()->create();
        $endpoint = WebhookEndpoint::factory()->create(['project_id' => $projectToDelete->id]);
        Event::factory()->count(5)->create([
            'webhook_endpoint_id' => $endpoint->id,
            'project_id' => $projectToDelete->id,
        ]);

        // Delete project
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->delete('/dashboard/projects/' . $projectToDelete->id, [
                '_token' => 'test-token'
            ]);

        $response->assertRedirect('/dashboard/projects');

        // Verify cascading deletion
        $this->assertDatabaseMissing('projects', ['id' => $projectToDelete->id]);
        $this->assertDatabaseMissing('webhook_endpoints', ['project_id' => $projectToDelete->id]);
        $this->assertDatabaseMissing('events', ['project_id' => $projectToDelete->id]);
    }

    /** @test */
    public function handles_memory_exhaustion_scenarios()
    {
        // Create a scenario that could cause memory issues
        $events = [];
        for ($i = 0; $i < 1000; $i++) {
            $events[] = [
                'webhook_endpoint_id' => $this->webhookEndpoint->id,
                'project_id' => $this->project->id,
                'payload' => json_encode(['large_data' => str_repeat('X', 1000)]),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks to avoid memory issues
        $chunks = array_chunk($events, 100);
        foreach ($chunks as $chunk) {
            Event::insert($chunk);
        }

        // Test that dashboard can handle large number of events
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events');

        $response->assertStatus(200);
    }

    /** @test */
    public function handles_unicode_and_special_characters()
    {
        // Test webhook with unicode characters
        $unicodePayload = [
            'message' => '🚀 Hello World! 你好世界 🌍',
            'emoji' => '😀😃😄😁😆😅😂🤣',
            'special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'unicode_text' => 'Iñtërnâtiônàlizætiøn',
        ];

        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, $unicodePayload);
        $response->assertStatus(200);

        // Verify data is stored correctly
        $event = Event::where('webhook_endpoint_id', $this->webhookEndpoint->id)->latest()->first();
        $this->assertEquals($unicodePayload, $event->payload);
    }
}