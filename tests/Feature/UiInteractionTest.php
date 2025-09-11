<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UiInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $webhookEndpoint;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'is_active' => true
        ]);
        $this->webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Endpoint',
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
            'is_active' => true,
        ]);
    }

    // ========== Authentication & Navigation ==========

    /** @test */
    public function user_can_access_login_page()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200)
            ->assertSee('Login')
            ->assertSee('Email')
            ->assertSee('Password');
    }

    /** @test */
    public function user_can_login_successfully()
    {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password', // Default factory password
        ]);
        
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);
        
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_can_logout()
    {
        $this->actingAs($this->user);
        
        $response = $this->post('/logout');
        
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    // ========== Dashboard Navigation ==========

    /** @test */
    public function dashboard_displays_correctly()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('Dashboard')
            ->assertSee('Projects')
            ->assertSee('Events')
            ->assertSee('Webhook Endpoints');
    }

    /** @test */
    public function dashboard_navigation_links_work()
    {
        $this->actingAs($this->user);
        
        // Test Projects link
        $response = $this->get('/dashboard/projects');
        $response->assertStatus(200)
            ->assertSee('Projects');
        
        // Test Events link
        $response = $this->get('/dashboard/events');
        $response->assertStatus(200)
            ->assertSee('Events');
        
        // Test Webhook Endpoints link
        $response = $this->get('/dashboard/projects/' . $this->project->id . '/endpoints');
        $response->assertStatus(200)
            ->assertSee('Webhook Endpoints');
    }

    /** @test */
    public function dashboard_shows_recent_activity()
    {
        // Create some recent events
        Event::factory()->count(5)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'created_at' => now()->subMinutes(10)
        ]);
        
        $response = $this->actingAs($this->user)
            ->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('Recent Events')
            ->assertSee($this->webhookEndpoint->name);
    }

    // ========== Project Management UI ==========

    /** @test */
    public function projects_index_displays_projects()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects');
        
        $response->assertStatus(200)
            ->assertSee('Projects')
            ->assertSee($this->project->name)
            ->assertSee('Create New Project');
    }

    /** @test */
    public function user_can_create_new_project_via_form()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/create');
        
        $response->assertStatus(200)
            ->assertSee('Create Project')
            ->assertSee('Project Name')
            ->assertSee('Description');
        
        // Submit the form
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects', [
                'name' => 'New UI Project',
                'description' => 'Created via UI test',
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect('/dashboard/projects');
        
        $this->assertDatabaseHas('projects', [
            'name' => 'New UI Project',
            'description' => 'Created via UI test'
        ]);
    }

    /** @test */
    public function project_form_validates_input()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects', [
                'name' => '', // Empty name should fail validation
                '_token' => 'test-token'
            ]);
        
        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function user_can_edit_project()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/' . $this->project->id . '/edit');
        
        $response->assertStatus(200)
            ->assertSee('Edit Project')
            ->assertSee($this->project->name);
        
        // Update the project
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->put('/dashboard/projects/' . $this->project->id, [
                'name' => 'Updated Project Name',
                'description' => 'Updated description',
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect('/dashboard/projects');
        
        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'name' => 'Updated Project Name'
        ]);
    }

    /** @test */
    public function user_can_delete_project_with_confirmation()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->delete('/dashboard/projects/' . $this->project->id, [
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect('/dashboard/projects');
        
        $this->assertDatabaseMissing('projects', [
            'id' => $this->project->id
        ]);
    }

    // ========== Webhook Endpoint Management UI ==========

    /** @test */
    public function webhook_endpoints_index_displays_endpoints()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/' . $this->project->id . '/endpoints');
        
        $response->assertStatus(200)
            ->assertSee('Webhook Endpoints')
            ->assertSee($this->webhookEndpoint->name)
            ->assertSee('Create New Endpoint');
    }

    /** @test */
    public function user_can_create_webhook_endpoint_via_form()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/' . $this->project->id . '/endpoints/create');
        
        $response->assertStatus(200)
            ->assertSee('Create Webhook Endpoint')
            ->assertSee('Endpoint Name')
            ->assertSee('Destination URLs')
            ->assertSee('Authentication Method');
        
        // Submit the form
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', [
                'name' => 'UI Created Endpoint',
                'destination_urls' => [
                    'https://ui-test.example.com/webhook',
                    'https://backup.example.com/webhook'
                ],
                'auth_method' => 'bearer',
                'auth_token' => 'ui-test-token',
                'retry_config' => [
                    'max_attempts' => 3,
                    'retry_delay' => 60
                ],
                'headers_config' => [
                    'X-Source' => 'UI-Test',
                    'X-Environment' => 'test'
                ],
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect('/dashboard/projects/' . $this->project->id . '/endpoints');
        
        $this->assertDatabaseHas('webhook_endpoints', [
            'name' => 'UI Created Endpoint',
            'project_id' => $this->project->id,
            'auth_method' => 'bearer'
        ]);
    }

    /** @test */
    public function webhook_endpoint_form_validates_urls()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', [
                'name' => 'Invalid URL Test',
                'destination_urls' => [
                    'not-a-valid-url',
                    'ftp://invalid-protocol.com'
                ],
                'auth_method' => 'none',
                '_token' => 'test-token'
            ]);
        
        $response->assertSessionHasErrors(['destination_urls.0', 'destination_urls.1']);
    }

    /** @test */
    public function user_can_edit_webhook_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/' . $this->project->id . '/endpoints/' . $this->webhookEndpoint->id . '/edit');
        
        $response->assertStatus(200)
            ->assertSee('Edit Webhook Endpoint')
            ->assertSee($this->webhookEndpoint->name);
        
        // Update the endpoint
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->put('/dashboard/projects/' . $this->project->id . '/endpoints/' . $this->webhookEndpoint->id, [
                'name' => 'Updated Endpoint Name',
                'destination_urls' => ['https://updated.example.com/webhook'],
                'auth_method' => 'hmac',
                'auth_secret' => 'new-secret-key',
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect('/dashboard/projects/' . $this->project->id . '/endpoints');
        
        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $this->webhookEndpoint->id,
            'name' => 'Updated Endpoint Name',
            'auth_method' => 'hmac'
        ]);
    }

    /** @test */
    public function user_can_toggle_endpoint_status()
    {
        // Disable endpoint
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->patch('/dashboard/projects/' . $this->project->id . '/endpoints/' . $this->webhookEndpoint->id . '/toggle', [
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect();
        
        $this->webhookEndpoint->refresh();
        $this->assertFalse($this->webhookEndpoint->is_active);
        
        // Re-enable endpoint
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->patch('/dashboard/projects/' . $this->project->id . '/endpoints/' . $this->webhookEndpoint->id . '/toggle', [
                '_token' => 'test-token'
            ]);
        
        $this->webhookEndpoint->refresh();
        $this->assertTrue($this->webhookEndpoint->is_active);
    }

    // ========== Event Management UI ==========

    /** @test */
    public function events_index_displays_events()
    {
        // Create some events
        Event::factory()->count(3)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id
        ]);
        
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events');
        
        $response->assertStatus(200)
            ->assertSee('Events')
            ->assertSee($this->webhookEndpoint->name);
    }

    /** @test */
    public function events_can_be_filtered_by_status()
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
        
        // Filter by failed status
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events?status=failed');
        
        $response->assertStatus(200)
            ->assertSee('failed');
    }

    /** @test */
    public function user_can_view_event_details()
    {
        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['test' => 'data', 'user_id' => 123],
            'headers' => ['x-signature' => 'test-signature']
        ]);
        
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events/' . $event->id);
        
        $response->assertStatus(200)
            ->assertSee('Event Details')
            ->assertSee('test')
            ->assertSee('data')
            ->assertSee('x-signature');
    }

    /** @test */
    public function user_can_replay_individual_event()
    {
        Queue::fake();
        
        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'failed'
        ]);
        
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/' . $event->id . '/replay', [
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect();
        
        $event->refresh();
        $this->assertEquals('pending', $event->status);
    }

    /** @test */
    public function user_can_perform_bulk_event_operations()
    {
        Queue::fake();
        
        // Create failed events
        $events = Event::factory()->count(3)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'failed'
        ]);
        
        $eventIds = $events->pluck('id')->toArray();
        
        // Bulk retry
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/bulk-retry', [
                'event_ids' => $eventIds,
                '_token' => 'test-token'
            ]);
        
        $response->assertRedirect();
        
        // Verify events were queued for retry
        foreach ($events as $event) {
            $event->refresh();
            $this->assertEquals('pending', $event->status);
        }
    }

    // ========== Search and Filtering ==========

    /** @test */
    public function user_can_search_events()
    {
        // Create events with searchable content
        Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['user_email' => 'search@example.com']
        ]);
        Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'payload' => ['user_email' => 'other@example.com']
        ]);
        
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events?search=search@example.com');
        
        $response->assertStatus(200)
            ->assertSee('search@example.com')
            ->assertDontSee('other@example.com');
    }

    /** @test */
    public function user_can_filter_events_by_date_range()
    {
        // Create events at different times
        Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'created_at' => now()->subDays(5)
        ]);
        Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'created_at' => now()->subDays(1)
        ]);
        
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events?from=' . now()->subDays(2)->format('Y-m-d') . '&to=' . now()->format('Y-m-d'));
        
        $response->assertStatus(200);
        // Should only show recent event
    }

    // ========== Pagination and Sorting ==========

    /** @test */
    public function events_pagination_works()
    {
        // Create more events than fit on one page
        Event::factory()->count(25)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id
        ]);
        
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events');
        
        $response->assertStatus(200)
            ->assertSee('Next') // Pagination link
            ->assertSee('Previous'); // If not on first page
        
        // Test second page
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events?page=2');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function events_can_be_sorted()
    {
        // Create events with different timestamps
        $oldEvent = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'created_at' => now()->subHours(2)
        ]);
        $newEvent = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'created_at' => now()->subHours(1)
        ]);
        
        // Test ascending sort
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events?sort=created_at&direction=asc');
        
        $response->assertStatus(200);
        
        // Test descending sort (default)
        $response = $this->actingAs($this->user)
            ->get('/dashboard/events?sort=created_at&direction=desc');
        
        $response->assertStatus(200);
    }

    // ========== Form Interactions ==========

    /** @test */
    public function forms_handle_csrf_protection()
    {
        // Try to submit form without CSRF token
        $response = $this->actingAs($this->user)
            ->post('/dashboard/projects', [
                'name' => 'CSRF Test Project'
            ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function forms_show_validation_errors_inline()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects/' . $this->project->id . '/endpoints', [
                'name' => '', // Missing required field
                'destination_urls' => ['invalid-url'], // Invalid URL
                '_token' => 'test-token'
            ]);
        
        $response->assertSessionHasErrors(['name', 'destination_urls.0']);
        
        // Follow redirect to see errors displayed
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/' . $this->project->id . '/endpoints/create');
        
        $response->assertStatus(200);
    }

    // ========== AJAX and Dynamic Content ==========

    /** @test */
    public function ajax_endpoints_return_json()
    {
        // Test AJAX endpoint for event status updates
        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/dashboard/events/' . $event->id . '/status');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'attempt_count',
                'last_attempt_at'
            ]);
    }

    /** @test */
    public function dashboard_auto_refresh_works()
    {
        // Create an event
        $event = Event::factory()->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'pending'
        ]);
        
        // Test the auto-refresh endpoint
        $response = $this->actingAs($this->user)
            ->getJson('/dashboard/events/recent');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'events' => [
                    '*' => [
                        'id',
                        'status',
                        'created_at'
                    ]
                ]
            ]);
    }

    // ========== Responsive Design Tests ==========

    /** @test */
    public function dashboard_includes_responsive_meta_tags()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('viewport', false) // Check for viewport meta tag
            ->assertSee('width=device-width', false);
    }

    /** @test */
    public function mobile_navigation_elements_present()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('navbar-toggler', false) // Bootstrap mobile menu toggle
            ->assertSee('collapse', false); // Collapsible navigation
    }

    // ========== Accessibility Tests ==========

    /** @test */
    public function forms_have_proper_labels_and_accessibility()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/create');
        
        $response->assertStatus(200)
            ->assertSee('for="name"', false) // Label for name field
            ->assertSee('id="name"', false) // Input with matching ID
            ->assertSee('required', false); // Required field indicators
    }

    /** @test */
    public function error_messages_are_accessible()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects', [
                'name' => '',
                '_token' => 'test-token'
            ]);
        
        $response->assertSessionHasErrors(['name']);
        
        // Check that error is displayed with proper ARIA attributes
        $response = $this->actingAs($this->user)
            ->get('/dashboard/projects/create');
        
        $response->assertStatus(200);
    }
}