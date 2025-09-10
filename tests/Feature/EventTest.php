<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventDelivery;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $webhookEndpoint;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
    }

    public function test_events_index_displays_correctly()
    {
        Event::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.events')
            ->assertViewHas('events');
    }

    public function test_event_can_be_created_via_webhook()
    {
        Queue::fake();

        $payload = [
            'user_id' => 123,
            'action' => 'user_created',
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson($this->webhookEndpoint->url_path, $payload, [
            'X-Webhook-Signature' => $this->generateSignature($payload),
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'received']);

        $this->assertDatabaseHas('events', [
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessWebhookEvent::class);
    }

    public function test_event_can_be_viewed()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'payload' => json_encode(['user_id' => 123, 'action' => 'test']),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events/' . $event->id);

        $response->assertStatus(200)
            ->assertViewIs('dashboard.events.show')
            ->assertViewHas('event')
            ->assertSee($event->event_id)
            ->assertSee('user_id');
    }

    public function test_event_can_be_replayed()
    {
        Queue::fake();

        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'event_type' => 'user.created',
            'payload' => json_encode(['user_id' => 123]),
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->postJson('/dashboard/events/' . $event->id . '/replay', [
                '_token' => 'test-token',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event replayed successfully',
            ])
            ->assertJsonStructure(['new_event_id']);

        // Verify a new event was created
        $this->assertDatabaseCount('events', 2);
        $newEvent = Event::where('id', '!=', $event->id)->first();
        $this->assertEquals($event->event_type, $newEvent->event_type);
        $this->assertEquals($event->payload, $newEvent->payload);
        $this->assertEquals('pending', $newEvent->status);

        Queue::assertPushed(ProcessWebhookEvent::class);
    }

    public function test_event_deliveries_are_tracked()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        // Create delivery attempts
        EventDelivery::factory()->create([
            'event_id' => $event->id,
            'destination_url' => 'https://example.com/webhook',
            'status' => 'success',
            'response_code' => 200,
            'latency_ms' => 150,
        ]);

        EventDelivery::factory()->create([
            'event_id' => $event->id,
            'destination_url' => 'https://backup.example.com/webhook',
            'status' => 'failed',
            'response_code' => 500,
            'latency_ms' => 5000,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events/' . $event->id);

        $response->assertStatus(200)
            ->assertSee('200') // success response code
            ->assertSee('500') // failed response code
            ->assertSee('150ms') // latency
            ->assertSee('5000ms'); // failed latency
    }

    public function test_events_can_be_filtered_by_status()
    {
        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'success',
        ]);

        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/events?status=success');

        $response->assertStatus(200);
        $events = $response->viewData('events');
        $this->assertCount(3, $events);
        
        foreach ($events as $event) {
            $this->assertEquals('success', $event->status);
        }
    }

    public function test_events_can_be_filtered_by_event_type()
    {
        Event::factory()->count(4)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'event_type' => 'user.created',
        ]);

        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'event_type' => 'user.updated',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/events?event_type=user.created');

        $response->assertStatus(200);
        $events = $response->viewData('events');
        $this->assertCount(4, $events);
        
        foreach ($events as $event) {
            $this->assertEquals('user.created', $event->event_type);
        }
    }

    public function test_events_can_be_filtered_by_date_range()
    {
        // Create events from different dates
        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'created_at' => now()->subDays(5),
        ]);

        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'created_at' => now()->subDays(2),
        ]);

        $dateFrom = now()->subDays(3)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/events?date_from=' . $dateFrom . '&date_to=' . $dateTo);

        $response->assertStatus(200);
        $events = $response->viewData('events');
        $this->assertCount(3, $events);
    }

    public function test_events_can_be_searched_in_payload()
    {
        Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'payload' => json_encode(['email' => 'john@example.com', 'action' => 'login']),
        ]);

        Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'payload' => json_encode(['email' => 'jane@example.com', 'action' => 'logout']),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/events?search=john@example.com');

        $response->assertStatus(200);
        $events = $response->viewData('events');
        $this->assertCount(1, $events);
        $this->assertStringContainsString('john@example.com', $events->first()->payload);
    }

    public function test_event_can_be_deleted()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $delivery = EventDelivery::factory()->create([
            'event_id' => $event->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->delete('/events/' . $event->id, [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/events');
        
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        // Related deliveries should also be deleted (cascade)
        $this->assertDatabaseMissing('event_deliveries', ['id' => $delivery->id]);
    }

    public function test_bulk_events_can_be_deleted()
    {
        $events = Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $eventIds = $events->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->delete('/events/bulk', [
                'event_ids' => $eventIds,
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/events');
        
        foreach ($eventIds as $eventId) {
            $this->assertDatabaseMissing('events', ['id' => $eventId]);
        }
    }

    public function test_event_statistics_are_calculated_correctly()
    {
        // Create events with different statuses
        Event::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'success',
            'created_at' => now()->subHours(12),
        ]);

        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'failed',
            'created_at' => now()->subHours(6),
        ]);

        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'pending',
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->getJson('/dashboard/events/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'total_events' => 15,
                'successful_events' => 10,
                'failed_events' => 3,
                'pending_events' => 2,
                'success_rate' => 66.67, // 10/15 * 100
            ]);
    }

    public function test_event_export_works()
    {
        Event::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="events.csv"');
    }

    public function test_event_payload_can_be_formatted()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'payload' => json_encode([
                'user' => [
                    'id' => 123,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'action' => 'created',
                'timestamp' => '2024-01-01T12:00:00Z',
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events/' . $event->id);

        $response->assertStatus(200)
            ->assertSee('John Doe')
            ->assertSee('john@example.com')
            ->assertSee('created');
    }

    public function test_webhook_signature_validation_works()
    {
        $payload = ['test' => 'data'];
        $invalidSignature = 'invalid-signature';

        $response = $this->postJson($this->webhookEndpoint->url_path, $payload, [
            'X-Webhook-Signature' => $invalidSignature,
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_inactive_webhook_endpoint_rejects_events()
    {
        $this->webhookEndpoint->update(['is_active' => false]);

        $payload = ['test' => 'data'];

        $response = $this->postJson($this->webhookEndpoint->url_path, $payload, [
            'X-Webhook-Signature' => $this->generateSignature($payload),
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(404);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_unauthenticated_users_cannot_access_events()
    {
        $response = $this->get('/dashboard/events');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_view_event_details()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->get('/dashboard/events/' . $event->id);
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_replay_events()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/' . $event->id . '/replay', [
                '_token' => 'test-token',
            ]);
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_access_event_details()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->post('/logout');
        
        $response = $this->get('/dashboard/event-details/' . $event->id);
        $response->assertRedirect('/login');
    }

    /**
     * Generate a webhook signature for testing
     */
    private function generateSignature(array $payload): string
    {
        $secret = $this->project->webhook_secret;
        $payloadString = json_encode($payload);
        
        return 'sha256=' . hash_hmac('sha256', $payloadString, $secret);
    }
}