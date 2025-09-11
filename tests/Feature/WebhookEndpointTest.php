<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'is_active' => true,
        ]);
    }

    public function test_webhook_endpoints_index_displays_correctly()
    {
        WebhookEndpoint::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            ->assertViewIs('dashboard.projects.show')
            ->assertViewHas('project');
    }

    public function test_webhook_endpoint_can_be_created()
    {
        $webhookData = [
            'name' => 'User Events Webhook',
            'destination_urls' => [
                'https://example.com/webhook',
                'https://backup.example.com/webhook',
            ],
            'auth_method' => 'hmac',
            'auth_secret' => 'secret-token-123',
            'is_active' => true,
            'retry_config' => [
                'max_attempts' => 3,
                'retry_delay' => 5,
            ],
            'headers_config' => [
                'X-Custom-Header' => 'custom-value',
            ],
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', $webhookData);

        $response->assertRedirect('/dashboard/projects/' . $this->project->id . '/endpoints/create');
        
        $this->assertDatabaseHas('webhook_endpoints', [
            'project_id' => $this->project->id,
            'name' => 'User Events Webhook',
            'auth_method' => 'hmac',
            'is_active' => true,
        ]);

        $webhook = WebhookEndpoint::where('name', 'User Events Webhook')->first();
        $this->assertEquals(['https://example.com/webhook', 'https://backup.example.com/webhook'], $webhook->destination_urls);
        $this->assertEquals(['max_attempts' => 3, 'retry_delay' => 5], $webhook->retry_config);
        $this->assertEquals(['X-Custom-Header' => 'custom-value'], $webhook->headers_config);
        $this->assertNotNull($webhook->slug);
        $this->assertNotNull($webhook->url_path);
    }

    public function test_webhook_endpoint_creation_requires_name()
    {
        $webhookData = [
            'destination_urls' => ['https://example.com/webhook'],
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', $webhookData);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('webhook_endpoints', 0);
    }

    public function test_webhook_endpoint_creation_requires_destination_urls()
    {
        $webhookData = [
            'name' => 'Test Webhook',
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', $webhookData);

        $response->assertSessionHasErrors(['destination_urls']);
        $this->assertDatabaseCount('webhook_endpoints', 0);
    }

    public function test_webhook_endpoint_can_be_viewed()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        Event::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/endpoints/' . $webhook->id . '/edit');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.endpoints.edit')
            ->assertViewHas('endpoint')
            ->assertSee($webhook->name);
    }

    public function test_webhook_endpoint_can_be_updated()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Webhook Name',
            'destination_urls' => ['https://updated.example.com/webhook'],
            'auth_method' => 'hmac',
            'auth_secret' => 'new-secret-456',
            'is_active' => false,
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->patch('/dashboard/endpoints/' . $webhook->id, $updateData);

        $response->assertRedirect('/dashboard/projects/' . $this->project->id);
        
        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $webhook->id,
            'name' => 'Updated Webhook Name',
            'auth_method' => 'hmac',
            'is_active' => false,
        ]);

        $webhook->refresh();
        $this->assertEquals(['https://updated.example.com/webhook'], $webhook->destination_urls);
    }

    public function test_webhook_endpoint_can_be_deleted()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->delete('/dashboard/endpoints/' . $webhook->id, [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/dashboard/projects/' . $this->project->id);
        
        $this->assertDatabaseMissing('webhook_endpoints', ['id' => $webhook->id]);
        // Related events should also be deleted (cascade)
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_webhook_endpoint_can_be_activated_and_deactivated()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => true,
        ]);

        // Deactivate
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->patch('/dashboard/endpoints/' . $webhook->id, [
                'name' => $webhook->name,
                'destination_urls' => $webhook->destination_urls,
                'auth_method' => 'none',
                'is_active' => false,
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/dashboard/projects/' . $webhook->project_id);
        $webhook->refresh();
        $this->assertFalse($webhook->is_active);

        // Activate
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->patch('/dashboard/endpoints/' . $webhook->id, [
                'name' => $webhook->name,
                'destination_urls' => $webhook->destination_urls,
                'auth_method' => 'none',
                'is_active' => true,
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/dashboard/projects/' . $webhook->project_id);
        $webhook->refresh();
        $this->assertTrue($webhook->is_active);
    }

    public function test_webhook_endpoint_url_path_is_generated_automatically()
    {
        $webhookData = [
            'name' => 'My Awesome Webhook!',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', $webhookData);

        $response->assertRedirect('/dashboard/projects/' . $this->project->id . '/endpoints/create');
        
        $webhook = WebhookEndpoint::where('name', 'My Awesome Webhook!')->first();
        $this->assertEquals('my-awesome-webhook', $webhook->slug);
        $this->assertStringStartsWith($this->project->slug, $webhook->url_path);
        $this->assertStringContainsString($this->project->slug, $webhook->url_path);
        $this->assertStringContainsString($webhook->slug, $webhook->url_path);
    }

    public function test_webhook_endpoint_slug_is_unique_within_project()
    {
        // Create first webhook
        WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'slug' => 'test-webhook',
        ]);

        $webhookData = [
            'name' => 'Test Webhook',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', $webhookData);

        $response->assertRedirect('/dashboard/projects/' . $this->project->id . '/endpoints/create');
        
        $webhook = WebhookEndpoint::where('name', 'Test Webhook')->first();
        $this->assertNotEquals('test-webhook', $webhook->slug);
        $this->assertStringStartsWith('test-webhook-', $webhook->slug);
    }

    public function test_webhook_endpoint_statistics_are_displayed()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        // Create events with different statuses
        Event::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
            'status' => 'success',
        ]);
        
        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
            'status' => 'failed',
        ]);
        
        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/endpoints/' . $webhook->id . '/events');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.endpoints.events')
            ->assertViewHas('events');
            
        $events = $response->viewData('events');
        $this->assertCount(15, $events);
    }

    public function test_webhook_endpoint_events_can_be_filtered()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        Event::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
            'event_type' => 'user.created',
        ]);
        
        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
            'event_type' => 'user.updated',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/endpoints/' . $webhook->id . '/events?event_type=user.created');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.endpoints.events')
            ->assertViewHas('events');

        $events = $response->viewData('events');
        $this->assertCount(5, $events);
        
        foreach ($events as $event) {
            $this->assertEquals('user.created', $event->event_type);
        }
    }

    public function test_webhook_endpoint_can_be_tested()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://httpbin.org/post'],
        ]);

        $testData = [
            'payload' => [
                'test' => true,
                'message' => 'This is a test webhook',
            ],
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->postJson('/dashboard/endpoints/' . $webhook->id . '/test', $testData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Test webhook sent successfully',
            ]);

        // Verify a test event was created
        $this->assertDatabaseHas('events', [
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $webhook->id,
            'event_type' => 'test',
        ]);
    }

    public function test_webhook_endpoint_retry_configuration_works()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'retry_config' => [
                'max_attempts' => 5,
                'retry_delay' => 10,
                'backoff_multiplier' => 2,
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/endpoints/' . $webhook->id . '/edit');

        $response->assertStatus(200)
            ->assertSee('5') // max_attempts
            ->assertSee('10') // retry_delay
            ->assertSee('2'); // backoff_multiplier
    }

    public function test_webhook_endpoint_headers_configuration_works()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'headers_config' => [
                'X-Custom-Header' => 'custom-value',
                'Authorization' => 'Bearer token123',
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/endpoints/' . $webhook->id . '/edit');

        $response->assertStatus(200)
            ->assertSee('X-Custom-Header')
            ->assertSee('custom-value');
    }

    public function test_unauthenticated_users_cannot_access_webhooks()
    {
        $response = $this->get('/dashboard/projects/' . $this->project->id);
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_create_webhooks()
    {
        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', [
                'name' => 'Test',
                'destination_urls' => ['https://example.com'],
                '_token' => 'test-token',
            ]);
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_update_webhooks()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $response = $this->withSession(['_token' => 'test-token'])
            ->patch('/dashboard/endpoints/' . $webhook->id, [
                'name' => 'Updated',
                '_token' => 'test-token',
            ]);
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_delete_webhooks()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $response = $this->withSession(['_token' => 'test-token'])
            ->delete('/dashboard/endpoints/' . $webhook->id, [
                '_token' => 'test-token',
            ]);
        $response->assertRedirect('/login');
    }

    // Short URL Tests
    public function test_webhook_endpoint_automatically_generates_short_url_on_creation()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->assertNotNull($webhook->short_url);
        $this->assertEquals(8, strlen($webhook->short_url));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $webhook->short_url);
    }

    public function test_short_url_is_unique_across_all_endpoints()
    {
        $webhook1 = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $webhook2 = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->assertNotEquals($webhook1->short_url, $webhook2->short_url);
    }

    public function test_short_url_uniqueness_across_multiple_endpoints()
    {
        $webhooks = WebhookEndpoint::factory()->count(10)->create([
            'project_id' => $this->project->id,
        ]);

        $shortUrls = $webhooks->pluck('short_url')->toArray();
        
        // Verify all short URLs are unique
        $this->assertEquals(count($shortUrls), count(array_unique($shortUrls)));
        
        // Verify all short URLs match the expected pattern
        foreach ($shortUrls as $shortUrl) {
            $this->assertEquals(8, strlen($shortUrl));
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $shortUrl);
        }
    }

    public function test_short_url_api_ingestion_endpoint_works()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
            'is_active' => true,
        ]);

        $payload = ['test' => 'data', 'timestamp' => now()->toISOString()];
        
        $response = $this->postJson('/api/w/' . $webhook->short_url, $payload, [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'event_id',
                'message'
            ]);
        
        // Verify event was created
        $this->assertDatabaseHas('events', [
            'webhook_endpoint_id' => $webhook->id,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_short_url_info_endpoint_returns_correct_data()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'bearer',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/w/' . $webhook->short_url . '/info');

        $response->assertStatus(200)
            ->assertJson([
                'project' => $this->project->name,
                'endpoint' => $webhook->name,
                'url_path' => $webhook->url_path,
                'short_url' => $webhook->short_url,
                'auth_method' => 'bearer',
                'is_active' => true,
                'destination_urls' => ['https://example.com/webhook'],
            ]);
    }

    public function test_short_url_ingestion_fails_for_inactive_endpoint()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/w/' . $webhook->short_url, ['test' => 'data']);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Webhook endpoint not found'
            ]);
    }

    public function test_short_url_endpoints_return_404_for_invalid_short_url()
    {
        $response = $this->postJson('/api/w/invalid123', ['test' => 'data']);
        $response->assertStatus(404);

        $response = $this->getJson('/api/w/invalid123/info');
        $response->assertStatus(404);
    }

    public function test_short_url_validation_in_routes()
    {
        // Test with invalid characters
        $response = $this->postJson('/api/w/invalid-url!', ['test' => 'data']);
        $response->assertStatus(404); // Should not match route pattern

        // Test with wrong length
        $response = $this->postJson('/api/w/short', ['test' => 'data']);
        $response->assertStatus(404); // Should not match route pattern

        $response = $this->postJson('/api/w/toolongshorturl', ['test' => 'data']);
        $response->assertStatus(404); // Should not match route pattern
    }

    public function test_webhook_endpoint_can_be_found_by_short_url()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $foundWebhook = WebhookEndpoint::where('short_url', $webhook->short_url)->first();
        
        $this->assertNotNull($foundWebhook);
        $this->assertEquals($webhook->id, $foundWebhook->id);
    }

    public function test_short_url_persists_after_endpoint_update()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Original Name',
        ]);

        $originalShortUrl = $webhook->short_url;

        // Update the webhook
        $webhook->update(['name' => 'Updated Name']);
        $webhook->refresh();

        $this->assertEquals($originalShortUrl, $webhook->short_url);
    }
}