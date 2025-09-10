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
        $this->project = Project::factory()->create();
    }

    public function test_webhook_endpoints_index_displays_correctly()
    {
        WebhookEndpoint::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id . '/webhooks');

        $response->assertStatus(200)
            ->assertViewIs('webhooks.index')
            ->assertViewHas(['webhooks', 'project']);
    }

    public function test_webhook_endpoint_can_be_created()
    {
        $webhookData = [
            'name' => 'User Events Webhook',
            'destination_urls' => [
                'https://example.com/webhook',
                'https://backup.example.com/webhook',
            ],
            'auth_method' => 'bearer',
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

        $response->assertRedirect('/dashboard/projects/' . $this->project->id);
        
        $this->assertDatabaseHas('webhook_endpoints', [
            'project_id' => $this->project->id,
            'name' => 'User Events Webhook',
            'auth_method' => 'bearer',
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
            ->get('/projects/' . $this->project->id . '/webhooks/' . $webhook->id);

        $response->assertStatus(200)
            ->assertViewIs('webhooks.show')
            ->assertViewHas(['webhook', 'project'])
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
            ->post('/projects/' . $this->project->id . '/webhooks/' . $webhook->id . '/toggle-status', [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/projects/' . $this->project->id . '/webhooks/' . $webhook->id);
        $webhook->refresh();
        $this->assertFalse($webhook->is_active);

        // Activate
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects/' . $this->project->id . '/webhooks/' . $webhook->id . '/toggle-status', [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/projects/' . $this->project->id . '/webhooks/' . $webhook->id);
        $webhook->refresh();
        $this->assertTrue($webhook->is_active);
    }

    public function test_webhook_endpoint_url_path_is_generated_automatically()
    {
        $webhookData = [
            'name' => 'My Awesome Webhook!',
            'destination_urls' => ['https://example.com/webhook'],
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects/' . $this->project->id . '/webhooks', $webhookData);

        $response->assertRedirect('/projects/' . $this->project->id . '/webhooks');
        
        $webhook = WebhookEndpoint::where('name', 'My Awesome Webhook!')->first();
        $this->assertEquals('my-awesome-webhook', $webhook->slug);
        $this->assertStringStartsWith('/webhook/', $webhook->url_path);
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
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects/' . $this->project->id . '/webhooks', $webhookData);

        $response->assertRedirect('/projects/' . $this->project->id . '/webhooks');
        
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
            ->get('/projects/' . $this->project->id . '/webhooks/' . $webhook->id . '/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'total_events' => 15,
                'successful_events' => 10,
                'failed_events' => 3,
                'pending_events' => 2,
                'success_rate' => 66.67, // 10/15 * 100
            ]);
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
            ->get('/dashboard/endpoints/' . $webhook->id . '/events');

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
}