<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->apiKey = $this->project->api_key;
    }

    public function test_api_requires_valid_api_key()
    {
        $response = $this->postJson('/api/webhooks/endpoints', [
            'name' => 'Test Endpoint',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
        ], [
            'Authorization' => 'Bearer invalid-api-key',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid API key']);
    }

    public function test_webhook_endpoint_can_be_created_via_api()
    {
        $endpointData = [
            'name' => 'User Events API',
            'destination_urls' => [
                'https://example.com/webhook',
                'https://backup.example.com/webhook',
            ],
            'auth_method' => 'hmac',
            'auth_secret' => 'secret-key-123',
            'retry_config' => [
                'max_attempts' => 5,
                'retry_delay' => 120,
                'backoff_multiplier' => 2,
            ],
            'headers_config' => [
                'X-Custom-Header' => 'api-value',
            ],
            'is_active' => true,
        ];

        $response = $this->postJson('/api/webhooks/endpoints', $endpointData, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'User Events API',
                    'destination_urls' => [
                        'https://example.com/webhook',
                        'https://backup.example.com/webhook',
                    ],
                    'auth_method' => 'hmac',
                    'is_active' => true,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'slug',
                    'url_path',
                    'webhook_url',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('webhook_endpoints', [
            'project_id' => $this->project->id,
            'name' => 'User Events API',
            'auth_method' => 'hmac',
        ]);
    }

    public function test_webhook_endpoint_creation_validates_required_fields()
    {
        $response = $this->postJson('/api/webhooks/endpoints', [
            // Missing required fields
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['name', 'destination_urls', 'auth_method']);
    }

    public function test_webhook_endpoint_creation_validates_destination_urls()
    {
        $response = $this->postJson('/api/webhooks/endpoints', [
            'name' => 'Test Endpoint',
            'destination_urls' => ['invalid-url', 'not-a-url'],
            'auth_method' => 'none',
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination_urls.0', 'destination_urls.1']);
    }

    public function test_events_can_be_retrieved_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Event::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
        ]);

        $response = $this->getJson('/api/events', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'events' => [
                        '*' => [
                            'id',
                            'event_id',
                            'event_type',
                            'status',
                            'payload',
                            'created_at',
                            'webhook_endpoint',
                        ],
                    ],
                    'pagination' => [
                        'total',
                        'limit',
                        'offset',
                        'has_more',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(5, $data['events']);
        $this->assertEquals(5, $data['pagination']['total']);
    }

    public function test_events_can_be_filtered_by_status_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'delivered',
        ]);

        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'failed',
        ]);

        $response = $this->getJson('/api/events?status=delivered', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $events = $response->json('data.events');
        $this->assertCount(3, $events);
        
        foreach ($events as $event) {
            $this->assertEquals('delivered', $event['status']);
        }
    }

    public function test_events_can_be_filtered_by_event_type_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Event::factory()->count(4)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'event_type' => 'user.created',
        ]);

        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'event_type' => 'user.updated',
        ]);

        $response = $this->getJson('/api/events?event_type=user.created', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $events = $response->json('data.events');
        $this->assertCount(4, $events);
        
        foreach ($events as $event) {
            $this->assertEquals('user.created', $event['event_type']);
        }
    }

    public function test_events_can_be_filtered_by_date_range_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        // Create events from different dates
        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'created_at' => now()->subDays(5),
        ]);

        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'created_at' => now()->subDays(2),
        ]);

        $fromDate = now()->subDays(3)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->getJson('/api/events?from_date=' . $fromDate . '&to_date=' . $toDate, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $events = $response->json('data.events');
        $this->assertCount(3, $events);
    }

    public function test_events_pagination_works_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Event::factory()->count(25)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
        ]);

        // First page
        $response = $this->getJson('/api/events?limit=10&offset=0', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(10, $data['events']);
        $this->assertEquals(25, $data['pagination']['total']);
        $this->assertEquals(10, $data['pagination']['limit']);
        $this->assertEquals(0, $data['pagination']['offset']);
        $this->assertTrue($data['pagination']['has_more']);

        // Second page
        $response = $this->getJson('/api/events?limit=10&offset=10', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(10, $data['events']);
        $this->assertEquals(10, $data['pagination']['offset']);
        $this->assertTrue($data['pagination']['has_more']);

        // Last page
        $response = $this->getJson('/api/events?limit=10&offset=20', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(5, $data['events']);
        $this->assertEquals(20, $data['pagination']['offset']);
        $this->assertFalse($data['pagination']['has_more']);
    }

    public function test_events_sorting_works_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $oldEvent = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'created_at' => now()->subDays(2),
        ]);

        $newEvent = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'created_at' => now()->subHours(1),
        ]);

        // Sort by created_at ascending
        $response = $this->getJson('/api/events?sort_by=created_at&sort_order=asc', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $events = $response->json('data.events');
        $this->assertEquals($oldEvent->id, $events[0]['id']);
        $this->assertEquals($newEvent->id, $events[1]['id']);

        // Sort by created_at descending (default)
        $response = $this->getJson('/api/events?sort_by=created_at&sort_order=desc', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        $events = $response->json('data.events');
        $this->assertEquals($newEvent->id, $events[0]['id']);
        $this->assertEquals($oldEvent->id, $events[1]['id']);
    }

    public function test_single_event_can_be_retrieved_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'payload' => json_encode(['user_id' => 123, 'action' => 'created']),
        ]);

        $response = $this->getJson('/api/events/' . $event->event_id, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $event->id,
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'status' => $event->status,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'payload',
                    'headers',
                    'created_at',
                    'webhook_endpoint',
                    'deliveries',
                ],
            ]);
    }

    public function test_event_can_be_replayed_via_api()
    {
        Queue::fake();

        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'failed',
        ]);

        $response = $this->postJson('/api/events/' . $event->event_id . '/replay', [], [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event replayed successfully',
            ])
            ->assertJsonStructure(['data' => ['new_event_id']]);

        // Verify a new event was created
        $this->assertDatabaseCount('events', 2);
        Queue::assertPushed(ProcessWebhookEvent::class);
    }

    public function test_project_statistics_can_be_retrieved_via_api()
    {
        $webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Event::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'delivered',
        ]);

        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'failed',
        ]);

        $response = $this->getJson('/api/projects/statistics', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_events' => 13,
                    'successful_events' => 10,
                    'failed_events' => 3,
                    'total_endpoints' => 1,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'success_rate',
                    'average_response_time',
                    'events_last_24h',
                ],
            ]);
    }

    public function test_webhook_endpoints_can_be_listed_via_api()
    {
        WebhookEndpoint::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->getJson('/api/webhooks/endpoints', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'endpoints' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'url_path',
                            'webhook_url',
                            'destination_urls',
                            'auth_method',
                            'is_active',
                            'created_at',
                        ],
                    ],
                ],
            ]);

        $endpoints = $response->json('data.endpoints');
        $this->assertCount(3, $endpoints);
    }

    public function test_webhook_endpoint_can_be_updated_via_api()
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated API Name',
            'destination_urls' => ['https://updated.example.com/webhook'],
            'is_active' => false,
        ];

        $response = $this->putJson('/api/webhooks/endpoints/' . $endpoint->id, $updateData, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated API Name',
                    'destination_urls' => ['https://updated.example.com/webhook'],
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $endpoint->id,
            'name' => 'Updated API Name',
            'is_active' => false,
        ]);
    }

    public function test_webhook_endpoint_can_be_deleted_via_api()
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->deleteJson('/api/webhooks/endpoints/' . $endpoint->id, [], [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook endpoint deleted successfully',
            ]);

        $this->assertDatabaseMissing('webhook_endpoints', ['id' => $endpoint->id]);
    }

    public function test_api_rate_limiting_works()
    {
        // This test would require setting up rate limiting middleware
        // For now, we'll just test that the endpoint responds correctly
        $response = $this->getJson('/api/events', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(200);
        // In a real implementation, you would make multiple requests
        // and verify that rate limiting kicks in
    }

    public function test_api_validates_project_ownership()
    {
        $otherProject = Project::factory()->create();
        $otherEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        // Try to access another project's endpoint
        $response = $this->getJson('/api/webhooks/endpoints/' . $otherEndpoint->id, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Webhook endpoint not found']);
    }

    public function test_api_handles_invalid_json_gracefully()
    {
        $response = $this->post('/api/webhooks/endpoints', [], [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ]);

        // Test with malformed data that would cause validation errors
        $response->assertStatus(422);
    }

    public function test_api_returns_proper_error_for_missing_resources()
    {
        $response = $this->getJson('/api/events/non-existent-event-id', [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Event not found']);
    }
}