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
use Tests\TestCase;

class IntegrationWorkflowTest extends TestCase
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
            'auth_method' => 'hmac',
            'auth_secret' => 'test-secret',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function complete_webhook_lifecycle_from_creation_to_delivery()
    {
        Queue::fake();
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200)
        ]);

        // Step 1: User creates a new webhook endpoint via dashboard
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', [
                'name' => 'Integration Test Webhook',
                'destination_urls' => ['https://example.com/integration-webhook'],
                'auth_method' => 'bearer',
                'auth_secret' => 'bearer-token-123',
                'is_active' => true,
                '_token' => 'test-token',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('webhook_endpoints', [
            'name' => 'Integration Test Webhook',
            'auth_method' => 'bearer',
        ]);

        $endpoint = WebhookEndpoint::where('name', 'Integration Test Webhook')->first();
        $this->assertNotNull($endpoint->short_url);
        $this->assertNotNull($endpoint->url_path);

        // Step 2: External service sends webhook to the endpoint
        $webhookPayload = [
            'event_type' => 'user.created',
            'user_id' => 12345,
            'timestamp' => now()->toISOString(),
            'data' => [
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]
        ];

        $response = $this->postJson('/api/webhook/' . $endpoint->url_path, $webhookPayload);
        $response->assertStatus(200);

        // Step 3: Verify event was created and queued for processing
        $this->assertDatabaseHas('events', [
            'webhook_endpoint_id' => $endpoint->id,
            'project_id' => $this->project->id,
        ]);

        Queue::assertPushed(ProcessWebhookEvent::class);

        // Step 4: User views the event in dashboard
        $event = Event::where('webhook_endpoint_id', $endpoint->id)->first();
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events/' . $event->id);

        $response->assertStatus(200)
            ->assertViewIs('dashboard.events.show')
            ->assertViewHas('event');
    }

    /** @test */
    public function webhook_processing_with_retry_mechanism()
    {
        Queue::fake();
        Http::fake([
            'example.com/*' => Http::sequence()
                ->push(['error' => 'server error'], 500)
                ->push(['error' => 'server error'], 500)
                ->push(['status' => 'received'], 200)
        ]);

        // Create endpoint with retry configuration
        $endpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://example.com/webhook'],
            'retry_config' => [
                'max_attempts' => 3,
                'retry_delay' => 1,
                'backoff_multiplier' => 2,
            ],
            'is_active' => true,
        ]);

        // Send webhook
        $response = $this->postJson('/api/webhook/' . $endpoint->url_path, [
            'event' => 'test.retry',
            'data' => ['test' => 'data']
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(ProcessWebhookEvent::class);

        // Verify event created with retry configuration
        $event = Event::where('webhook_endpoint_id', $endpoint->id)->first();
        $this->assertNotNull($event);
        $this->assertEquals(0, $event->attempt_count);
    }

    /** @test */
    public function short_url_webhook_ingestion_workflow()
    {
        Queue::fake();

        // Send webhook using short URL format
        $response = $this->postJson('/api/w/' . $this->webhookEndpoint->short_url, [
            'event_type' => 'payment.completed',
            'amount' => 99.99,
            'currency' => 'USD',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'received']);

        // Verify event was created
        $this->assertDatabaseHas('events', [
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
        ]);

        Queue::assertPushed(ProcessWebhookEvent::class);
    }

    /** @test */
    public function api_based_webhook_endpoint_creation_and_usage()
    {
        Queue::fake();

        // Create endpoint via API
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'API Created Endpoint',
            'destination_urls' => ['https://api-test.example.com/webhook'],
            'auth_method' => 'none',
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $this->project->api_key,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'url_path',
                    'short_url',
                    'webhook_url',
                ]
            ]);

        $endpointData = $response->json('data');

        // Use the created endpoint
        $webhookResponse = $this->postJson('/api/webhook/' . $endpointData['url_path'], [
            'api_event' => 'test.created',
            'timestamp' => now()->toISOString(),
        ]);

        $webhookResponse->assertStatus(200);
        Queue::assertPushed(ProcessWebhookEvent::class);
    }

    /** @test */
    public function event_replay_functionality_workflow()
    {
        Queue::fake();
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200)
        ]);

        // Create an event
        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'payload' => ['test' => 'replay_data'],
        ]);

        // Replay event via dashboard
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/' . $event->id . '/replay', [
                '_token' => 'test-token'
            ]);

        $response->assertRedirect();
        Queue::assertPushed(ProcessWebhookEvent::class);

        // Replay event via API
        $apiResponse = $this->postJson('/api/v1/events/' . $event->id . '/replay', [], [
            'Authorization' => 'Bearer ' . $this->project->api_key,
        ]);

        $apiResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function bulk_event_operations_workflow()
    {
        Queue::fake();

        // Create multiple failed events
        $events = Event::factory()->count(5)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $eventIds = $events->pluck('id')->toArray();

        // Bulk retry via dashboard
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/bulk-retry', [
                'event_ids' => $eventIds,
                '_token' => 'test-token'
            ]);

        $response->assertRedirect();
        Queue::assertPushed(ProcessWebhookEvent::class, 5);

        // Bulk retry via API
        $apiResponse = $this->postJson('/api/v1/events/replay', [
            'event_ids' => $eventIds
        ], [
            'Authorization' => 'Bearer ' . $this->project->api_key,
        ]);

        $apiResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function project_lifecycle_with_endpoints_and_events()
    {
        // Create project via dashboard
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects', [
                'name' => 'Integration Test Project',
                'description' => 'Project for integration testing',
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/dashboard/projects');
        
        $project = Project::where('name', 'Integration Test Project')->first();
        $this->assertNotNull($project);
        $this->assertNotNull($project->api_key);

        // Create endpoint for the project
        $endpointResponse = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $project->id . '/endpoints', [
                'name' => 'Project Test Endpoint',
                'destination_urls' => ['https://project-test.example.com/webhook'],
                'auth_method' => 'none',
                'is_active' => true,
                '_token' => 'test-token',
            ]);

        $endpointResponse->assertRedirect();

        // View project dashboard
        $dashboardResponse = $this->actingAs($this->user)
            ->get('/dashboard/projects/' . $project->id);

        $dashboardResponse->assertStatus(200)
            ->assertViewIs('dashboard.projects.show')
            ->assertViewHas('project')
            ->assertSee('Project Test Endpoint');

        // Delete project (should cascade delete endpoints)
        $deleteResponse = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->delete('/dashboard/projects/' . $project->id, [
                '_token' => 'test-token'
            ]);

        $deleteResponse->assertRedirect('/dashboard/projects');
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('webhook_endpoints', ['project_id' => $project->id]);
    }

    /** @test */
    public function webhook_info_endpoint_integration()
    {
        // Get webhook info using regular URL path
        $response = $this->getJson('/api/webhook/' . $this->webhookEndpoint->url_path . '/info');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'project_name',
                'auth_method',
                'is_active',
                'created_at',
            ]);

        // Get webhook info using short URL
        $shortResponse = $this->getJson('/api/w/' . $this->webhookEndpoint->short_url . '/info');
        
        $shortResponse->assertStatus(200)
            ->assertJson($response->json());
    }

    /** @test */
    public function dashboard_charts_and_analytics_integration()
    {
        // Create events for chart data
        Event::factory()->count(10)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'created_at' => now()->subDays(1),
        ]);

        Event::factory()->count(15)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'created_at' => now(),
        ]);

        // Test global events chart
        $chartResponse = $this->actingAs($this->user)
            ->getJson('/dashboard/events-chart');

        $chartResponse->assertStatus(200)
            ->assertJsonStructure([
                'labels',
                'datasets' => [
                    '*' => [
                        'label',
                        'data',
                        'backgroundColor',
                    ]
                ]
            ]);

        // Test project-specific events chart
        $projectChartResponse = $this->actingAs($this->user)
            ->getJson('/dashboard/projects/' . $this->project->id . '/events-chart');

        $projectChartResponse->assertStatus(200)
            ->assertJsonStructure([
                'labels',
                'datasets'
            ]);
    }
}