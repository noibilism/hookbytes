<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_projects_index_displays_correctly()
    {
        Project::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.projects')
            ->assertViewHas('projects');
    }

    public function test_project_can_be_created()
    {
        $projectData = [
            'name' => 'Test Project',
            'description' => 'A test project for webhooks',
            'require_https' => true,
            'encrypt_payloads' => false,
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects', $projectData);

        $response->assertRedirect('/dashboard/projects');
        
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'description' => 'A test project for webhooks',
            'require_https' => true,
            'encrypt_payloads' => false,
        ]);

        $project = Project::where('name', 'Test Project')->first();
        $this->assertNotNull($project->api_key);
        $this->assertNotNull($project->webhook_secret);
        $this->assertNotNull($project->slug);
    }

    public function test_project_creation_requires_name()
    {
        $projectData = [
            'description' => 'A test project without name',
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects', $projectData);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_can_be_viewed()
    {
        $project = Project::factory()->create();
        WebhookEndpoint::factory()->count(2)->create(['project_id' => $project->id]);
        Event::factory()->count(5)->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $project->id);

        $response->assertStatus(200)
            ->assertViewIs('dashboard.projects.show')
            ->assertViewHas('project')
            ->assertSee($project->name);
    }

    public function test_project_can_be_updated()
    {
        $project = Project::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'require_https' => true,
            'encrypt_payloads' => true,
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->put('/projects/' . $project->id, $updateData);

        $response->assertRedirect('/projects/' . $project->id);
        
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'require_https' => true,
            'encrypt_payloads' => true,
        ]);
    }

    public function test_project_can_be_deleted()
    {
        $project = Project::factory()->create();
        $webhookEndpoint = WebhookEndpoint::factory()->create(['project_id' => $project->id]);
        $event = Event::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->delete('/dashboard/projects/' . $project->id, [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/dashboard/projects');
        
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        // Related data should also be deleted (cascade)
        $this->assertDatabaseMissing('webhook_endpoints', ['id' => $webhookEndpoint->id]);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_project_api_key_can_be_regenerated()
    {
        $project = Project::factory()->create();
        $originalApiKey = $project->api_key;

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects/' . $project->id . '/regenerate-api-key', [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/projects/' . $project->id);
        
        $project->refresh();
        $this->assertNotEquals($originalApiKey, $project->api_key);
        $this->assertNotNull($project->api_key);
    }

    public function test_project_webhook_secret_can_be_regenerated()
    {
        $project = Project::factory()->create();
        $originalSecret = $project->webhook_secret;

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects/' . $project->id . '/regenerate-webhook-secret', [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/projects/' . $project->id);
        
        $project->refresh();
        $this->assertNotEquals($originalSecret, $project->webhook_secret);
        $this->assertNotNull($project->webhook_secret);
    }

    public function test_project_can_be_activated_and_deactivated()
    {
        $project = Project::factory()->create(['is_active' => true]);

        // Deactivate
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects/' . $project->id . '/toggle-status', [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/projects/' . $project->id);
        $project->refresh();
        $this->assertFalse($project->is_active);

        // Activate
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects/' . $project->id . '/toggle-status', [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect('/projects/' . $project->id);
        $project->refresh();
        $this->assertTrue($project->is_active);
    }

    public function test_project_settings_can_be_updated()
    {
        $project = Project::factory()->create();

        $settingsData = [
            'allowed_ips' => ['192.168.1.1', '10.0.0.1'],
            'rate_limits' => [
                'requests_per_minute' => 100,
                'requests_per_hour' => 1000,
            ],
            'permissions' => ['read', 'write'],
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->put('/projects/' . $project->id . '/settings', $settingsData);

        $response->assertRedirect('/projects/' . $project->id);
        
        $project->refresh();
        $this->assertEquals(['192.168.1.1', '10.0.0.1'], $project->allowed_ips);
        $this->assertEquals(100, $project->rate_limits['requests_per_minute']);
        $this->assertEquals(['read', 'write'], $project->permissions);
    }

    public function test_project_statistics_are_displayed()
    {
        $project = Project::factory()->create();
        $webhookEndpoint = WebhookEndpoint::factory()->create(['project_id' => $project->id]);
        
        // Create events with different statuses
        Event::factory()->count(5)->create([
            'project_id' => $project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'success',
        ]);
        
        Event::factory()->count(2)->create([
            'project_id' => $project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'failed',
        ]);
        
        Event::factory()->count(1)->create([
            'project_id' => $project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/projects/' . $project->id . '/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'total_events' => 8,
                'successful_events' => 5,
                'failed_events' => 2,
                'pending_events' => 1,
                'total_endpoints' => 1,
            ]);
    }

    public function test_project_events_can_be_exported()
    {
        $project = Project::factory()->create();
        $webhookEndpoint = WebhookEndpoint::factory()->create(['project_id' => $project->id]);
        Event::factory()->count(10)->create([
            'project_id' => $project->id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/projects/' . $project->id . '/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="project-' . $project->slug . '-events.csv"');
    }

    public function test_project_slug_is_generated_automatically()
    {
        $projectData = [
            'name' => 'My Awesome Project!',
            'description' => 'Test description',
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects', $projectData);

        $response->assertRedirect('/projects');
        
        $project = Project::where('name', 'My Awesome Project!')->first();
        $this->assertEquals('my-awesome-project', $project->slug);
    }

    public function test_project_slug_is_unique()
    {
        // Create first project
        Project::factory()->create(['slug' => 'test-project']);

        $projectData = [
            'name' => 'Test Project',
            'description' => 'Another test project',
            '_token' => 'test-token',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/projects', $projectData);

        $response->assertRedirect('/projects');
        
        $project = Project::where('name', 'Test Project')->first();
        $this->assertNotEquals('test-project', $project->slug);
        $this->assertStringStartsWith('test-project-', $project->slug);
    }

    public function test_unauthenticated_users_cannot_access_projects()
    {
        $response = $this->get('/dashboard/projects');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_create_projects()
    {
        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/dashboard/projects', [
                'name' => 'Test',
                '_token' => 'test-token',
            ]);
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_update_projects()
    {
        $project = Project::factory()->create();
        $response = $this->withSession(['_token' => 'test-token'])
            ->patch('/dashboard/projects/' . $project->id, [
                'name' => 'Updated',
                '_token' => 'test-token',
            ]);
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_cannot_delete_projects()
    {
        $project = Project::factory()->create();
        $response = $this->withSession(['_token' => 'test-token'])
            ->delete('/dashboard/projects/' . $project->id, [
                '_token' => 'test-token',
            ]);
        $response->assertRedirect('/login');
    }
}