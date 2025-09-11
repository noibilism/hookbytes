<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $webhookEndpoint;
    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['is_active' => true]);
        $this->apiKey = $this->project->api_key;
        $this->webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
            'is_active' => true,
        ]);
    }

    // ========== Webhook Ingestion Endpoints ==========

    /** @test */
    public function webhook_ingestion_accepts_valid_payloads()
    {
        Queue::fake();
        
        $payload = [
            'event_type' => 'user.created',
            'user_id' => 123,
            'timestamp' => now()->toISOString(),
            'data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ];

        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'received',
                'event_id' => true
            ]);

        $this->assertDatabaseHas('events', [
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function webhook_ingestion_via_short_url_works()
    {
        Queue::fake();
        
        $payload = ['test' => 'data'];

        $response = $this->postJson('/api/w/' . $this->webhookEndpoint->short_url, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'received',
                'event_id' => true
            ]);

        $this->assertDatabaseHas('events', [
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'payload' => json_encode($payload)
        ]);
    }

    /** @test */
    public function webhook_ingestion_handles_different_content_types()
    {
        Queue::fake();
        
        // Test application/json
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'type' => 'json_test'
        ]);
        $response->assertStatus(200);

        // Test application/x-www-form-urlencoded
        $response = $this->post('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'type' => 'form_test'
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);
        $response->assertStatus(200);

        // Test text/plain
        $response = $this->call('POST', '/api/webhook/' . $this->webhookEndpoint->url_path, [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], 'plain text payload');
        $response->assertStatus(200);
    }

    /** @test */
    public function webhook_ingestion_captures_headers()
    {
        Queue::fake();
        
        $headers = [
            'X-Custom-Header' => 'custom-value',
            'X-Signature' => 'sha256=signature',
            'User-Agent' => 'TestClient/1.0'
        ];

        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, 
            ['test' => 'data'], 
            $headers
        );

        $response->assertStatus(200);

        $event = Event::where('webhook_endpoint_id', $this->webhookEndpoint->id)->latest()->first();
        $this->assertArrayHasKey('x-custom-header', $event->headers);
        $this->assertEquals('custom-value', $event->headers['x-custom-header']);
    }

    // ========== Webhook Info Endpoints ==========

    /** @test */
    public function webhook_info_returns_correct_data()
    {
        $response = $this->getJson('/api/webhook/' . $this->webhookEndpoint->url_path . '/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'url_path',
                'short_url',
                'destination_urls',
                'auth_method',
                'is_active',
                'created_at',
                'project' => [
                    'id',
                    'name',
                    'is_active'
                ]
            ])
            ->assertJson([
                'id' => $this->webhookEndpoint->id,
                'name' => $this->webhookEndpoint->name,
                'url_path' => $this->webhookEndpoint->url_path,
                'short_url' => $this->webhookEndpoint->short_url,
                'is_active' => true
            ]);
    }

    /** @test */
    public function webhook_info_via_short_url_works()
    {
        $response = $this->getJson('/api/w/' . $this->webhookEndpoint->short_url . '/info');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->webhookEndpoint->id,
                'short_url' => $this->webhookEndpoint->short_url
            ]);
    }

    /** @test */
    public function webhook_info_excludes_sensitive_data()
    {
        $hmacEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'auth_method' => 'hmac',
            'auth_secret' => 'secret-key'
        ]);

        $response = $this->getJson('/api/webhook/' . $hmacEndpoint->url_path . '/info');

        $response->assertStatus(200)
            ->assertJsonMissing(['auth_secret'])
            ->assertJson(['auth_method' => 'hmac']);
    }

    // ========== Authenticated API Endpoints ==========

    /** @test */
    public function api_requires_valid_authentication()
    {
        // Test without API key
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Test Endpoint'
        ]);
        $response->assertStatus(401);

        // Test with invalid API key
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Test Endpoint'
        ], [
            'Authorization' => 'Bearer invalid-key'
        ]);
        $response->assertStatus(401);

        // Test with valid API key
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Test Endpoint',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none'
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);
        $response->assertStatus(201);
    }

    /** @test */
    public function create_webhook_endpoint_via_api()
    {
        $endpointData = [
            'name' => 'API Created Endpoint',
            'destination_urls' => [
                'https://api.example.com/webhook',
                'https://backup.example.com/webhook'
            ],
            'auth_method' => 'bearer',
            'auth_token' => 'bearer-token-123',
            'retry_config' => [
                'max_attempts' => 5,
                'retry_delay' => 30
            ],
            'headers_config' => [
                'X-Source' => 'webhook-gateway',
                'X-Version' => '1.0'
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/endpoints', $endpointData, [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'url_path',
                'short_url',
                'destination_urls',
                'auth_method',
                'retry_config',
                'headers_config',
                'is_active',
                'created_at'
            ])
            ->assertJson([
                'name' => 'API Created Endpoint',
                'auth_method' => 'bearer',
                'is_active' => true
            ]);

        $this->assertDatabaseHas('webhook_endpoints', [
            'name' => 'API Created Endpoint',
            'project_id' => $this->project->id,
            'auth_method' => 'bearer'
        ]);
    }

    /** @test */
    public function create_webhook_endpoint_validates_input()
    {
        // Test missing required fields
        $response = $this->postJson('/api/v1/webhooks/endpoints', [], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'destination_urls', 'auth_method']);

        // Test invalid destination URLs
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Invalid URLs',
            'destination_urls' => ['not-a-url', 'ftp://invalid-protocol.com'],
            'auth_method' => 'none'
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination_urls.0', 'destination_urls.1']);

        // Test invalid auth method
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Invalid Auth',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'invalid_method'
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auth_method']);
    }

    /** @test */
    public function get_events_via_api()
    {
        // Create some events
        $events = Event::factory()->count(5)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id
        ]);

        $response = $this->getJson('/api/v1/events', [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'webhook_endpoint_id',
                        'project_id',
                        'payload',
                        'headers',
                        'status',
                        'attempt_count',
                        'created_at',
                        'webhook_endpoint' => [
                            'id',
                            'name',
                            'url_path'
                        ]
                    ]
                ],
                'links',
                'meta'
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function get_events_supports_filtering()
    {
        // Create events with different statuses
        Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'pending'
        ]);
        Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'delivered'
        ]);
        Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'failed'
        ]);

        // Filter by status
        $response = $this->getJson('/api/v1/events?status=pending', [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(200);
        $events = $response->json('data');
        $this->assertCount(1, $events);
        $this->assertEquals('pending', $events[0]['status']);

        // Filter by webhook endpoint
        $response = $this->getJson('/api/v1/events?webhook_endpoint_id=' . $this->webhookEndpoint->id, [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function get_events_supports_pagination()
    {
        // Create more events than the default page size
        Event::factory()->count(25)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id
        ]);

        // Test first page
        $response = $this->getJson('/api/v1/events?per_page=10', [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(3, $response->json('meta.last_page'));

        // Test second page
        $response = $this->getJson('/api/v1/events?page=2&per_page=10', [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.current_page'));
    }

    /** @test */
    public function replay_event_via_api()
    {
        Queue::fake();
        
        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'failed'
        ]);

        $response = $this->postJson('/api/v1/events/' . $event->id . '/replay', [], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'queued',
                'message' => 'Event queued for replay'
            ]);

        // Verify event status was updated
        $event->refresh();
        $this->assertEquals('pending', $event->status);
    }

    /** @test */
    public function replay_event_validates_ownership()
    {
        // Create event for different project
        $otherProject = Project::factory()->create();
        $otherEndpoint = WebhookEndpoint::factory()->create(['project_id' => $otherProject->id]);
        $otherEvent = Event::factory()->create([
            'webhook_endpoint_id' => $otherEndpoint->id,
            'project_id' => $otherProject->id
        ]);

        $response = $this->postJson('/api/v1/events/' . $otherEvent->id . '/replay', [], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(404); // Should not find event from different project
    }

    // ========== API Response Format Tests ==========

    /** @test */
    public function api_responses_have_consistent_format()
    {
        // Test successful response format
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Format Test',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none'
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'url_path',
                'short_url',
                'destination_urls',
                'auth_method',
                'is_active',
                'created_at'
            ]);

        // Test error response format
        $response = $this->postJson('/api/v1/webhooks/endpoints', [], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'destination_urls',
                    'auth_method'
                ]
            ]);
    }

    /** @test */
    public function api_handles_content_negotiation()
    {
        // Test JSON response (default)
        $response = $this->getJson('/api/webhook/' . $this->webhookEndpoint->url_path . '/info');
        $response->assertHeader('Content-Type', 'application/json');

        // Test with Accept header
        $response = $this->get('/api/webhook/' . $this->webhookEndpoint->url_path . '/info', [
            'Accept' => 'application/json'
        ]);
        $response->assertHeader('Content-Type', 'application/json');
    }

    // ========== Rate Limiting Tests ==========

    /** @test */
    public function api_enforces_rate_limiting()
    {
        // Make multiple rapid requests
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
                'test' => 'rate_limit_' . $i
            ]);
        }

        // Check if any requests were rate limited
        $rateLimitedCount = collect($responses)->filter(function ($response) {
            return $response->getStatusCode() === 429;
        })->count();

        // Should have some rate limiting after many requests
        $this->assertGreaterThan(0, $rateLimitedCount);
    }

    // ========== CORS Tests ==========

    /** @test */
    public function api_handles_cors_requests()
    {
        // Test preflight request
        $response = $this->call('OPTIONS', '/api/webhook/' . $this->webhookEndpoint->url_path, [], [], [], [
            'HTTP_ORIGIN' => 'https://example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Content-Type'
        ]);

        $response->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeader('Access-Control-Allow-Methods')
            ->assertHeader('Access-Control-Allow-Headers');

        // Test actual CORS request
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, 
            ['test' => 'cors'], 
            ['Origin' => 'https://example.com']
        );

        $response->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    // ========== API Versioning Tests ==========

    /** @test */
    public function api_versioning_works_correctly()
    {
        // Test v1 API
        $response = $this->getJson('/api/v1/events', [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);
        $response->assertStatus(200);

        // Test that invalid versions return 404
        $response = $this->getJson('/api/v2/events', [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);
        $response->assertStatus(404);
    }

    // ========== API Documentation Tests ==========

    /** @test */
    public function api_endpoints_return_proper_http_methods()
    {
        // Test that GET endpoints don't accept POST
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path . '/info');
        $response->assertStatus(405); // Method Not Allowed

        // Test that POST endpoints don't accept GET
        $response = $this->getJson('/api/webhook/' . $this->webhookEndpoint->url_path);
        $response->assertStatus(405);
    }

    /** @test */
    public function api_returns_proper_status_codes()
    {
        Queue::fake();
        
        // Test 200 for successful webhook ingestion
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, ['test' => 'data']);
        $response->assertStatus(200);

        // Test 201 for successful resource creation
        $response = $this->postJson('/api/v1/webhooks/endpoints', [
            'name' => 'Status Test',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none'
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);
        $response->assertStatus(201);

        // Test 404 for non-existent resources
        $response = $this->getJson('/api/webhook/nonexistent/info');
        $response->assertStatus(404);

        // Test 422 for validation errors
        $response = $this->postJson('/api/v1/webhooks/endpoints', [], [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);
        $response->assertStatus(422);
    }
}