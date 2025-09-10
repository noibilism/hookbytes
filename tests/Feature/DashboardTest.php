<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventDelivery;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_index_displays_correctly()
    {
        // Create some test data
        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.index')
            ->assertViewHas(['projects', 'recentEvents', 'stats']);
    }

    public function test_dashboard_shows_project_counts()
    {
        // Create events and webhook endpoints for the project
        Event::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);
        
        WebhookEndpoint::factory()->count(2)->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard');

        $response->assertStatus(200);
        
        $projects = $response->viewData('projects');
        $this->assertCount(1, $projects);
        $this->assertEquals(5, $projects->first()->events_count);
        $this->assertEquals(3, $projects->first()->webhook_endpoints_count); // 1 from setUp + 2 created
    }

    public function test_events_page_displays_correctly()
    {
        Event::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.events')
            ->assertViewHas(['events', 'projects', 'endpoints']);
    }

    public function test_events_can_be_filtered_by_project()
    {
        $anotherProject = Project::factory()->create();
        $anotherEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $anotherProject->id,
        ]);

        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);
        
        Event::factory()->count(2)->create([
            'project_id' => $anotherProject->id,
            'webhook_endpoint_id' => $anotherEndpoint->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events?project_id=' . $this->project->id);

        $response->assertStatus(200);
        $events = $response->viewData('events');
        $this->assertCount(3, $events);
        
        foreach ($events as $event) {
            $this->assertEquals($this->project->id, $event->project_id);
        }
    }

    public function test_events_can_be_filtered_by_status()
    {
        Event::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'success',
        ]);
        
        Event::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events?status=success');

        $response->assertStatus(200);
        $events = $response->viewData('events');
        $this->assertCount(2, $events);
        
        foreach ($events as $event) {
            $this->assertEquals('success', $event->status);
        }
    }

    public function test_events_can_be_searched_in_payload()
    {
        Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'payload' => json_encode(['user_id' => 123, 'action' => 'login']),
        ]);
        
        Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'payload' => json_encode(['user_id' => 456, 'action' => 'logout']),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events?search=login');

        $response->assertStatus(200);
        $events = $response->viewData('events');
        $this->assertCount(1, $events);
        $this->assertStringContainsString('login', $events->first()->payload);
    }

    public function test_event_details_page_displays_correctly()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/events/' . $event->id);

        $response->assertStatus(200)
            ->assertViewIs('dashboard.event-details')
            ->assertViewHas('event');
    }

    public function test_event_can_be_replayed()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'event_type' => 'user.created',
            'payload' => json_encode(['user_id' => 123]),
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
    }

    public function test_events_chart_returns_data()
    {
        Event::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->getJson('/dashboard/events/chart?today=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'labels',
                'data',
            ]);
    }

    public function test_delivery_stats_are_calculated_correctly()
    {
        // Create events from last 24 hours
        $recentEvent = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'created_at' => now()->subHours(12),
            'status' => 'pending',
        ]);
        
        // Create old event (should not be counted)
        Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'created_at' => now()->subDays(2),
        ]);

        // Create deliveries
        EventDelivery::factory()->create([
            'event_id' => $recentEvent->id,
            'status' => 'success',
            'latency_ms' => 150,
            'created_at' => now()->subHours(12),
        ]);
        
        EventDelivery::factory()->create([
            'event_id' => $recentEvent->id,
            'status' => 'failed',
            'created_at' => now()->subHours(6),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard');

        $response->assertStatus(200);
        $stats = $response->viewData('stats');
        
        $this->assertEquals(1, $stats['total_events']);
        $this->assertEquals(1, $stats['successful_deliveries']);
        $this->assertEquals(1, $stats['failed_deliveries']);
        $this->assertEquals(1, $stats['pending_events']);
        $this->assertEquals(150, $stats['average_response_time']);
    }

    public function test_unauthenticated_users_cannot_access_dashboard()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_access_events()
    {
        $response = $this->get('/dashboard/events');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_replay_events()
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id,
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
        ]);

        $response = $this->postJson('/dashboard/events/' . $event->id . '/replay');
        $response->assertStatus(401);
    }
}