<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardShortUrlTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    public function test_dashboard_displays_short_url_in_webhook_endpoints_table()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            ->assertSee('Short:')
            ->assertSee('/api/w/' . $webhook->short_url)
            ->assertSee($webhook->short_url);
    }

    public function test_dashboard_displays_both_full_and_short_urls()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            // Check for full URL display
            ->assertSee('Full:')
            ->assertSee('/api/webhook/' . $webhook->url_path)
            // Check for short URL display
            ->assertSee('Short:')
            ->assertSee('/api/w/' . $webhook->short_url)
            // Check for copy buttons
            ->assertSee('fas fa-copy');
    }

    public function test_dashboard_shows_short_url_with_correct_styling()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            // Check for blue styling on short URL
            ->assertSee('bg-blue-50')
            ->assertSee('text-blue-800')
            // Check for gray styling on full URL
            ->assertSee('bg-gray-100')
            ->assertSee('text-gray-800');
    }

    public function test_dashboard_handles_multiple_webhook_endpoints_with_short_urls()
    {
        $webhook1 = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'First Webhook',
        ]);
        
        $webhook2 = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Second Webhook',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            // Check both webhooks are displayed
            ->assertSee('First Webhook')
            ->assertSee('Second Webhook')
            // Check both short URLs are displayed
            ->assertSee('/api/w/' . $webhook1->short_url)
            ->assertSee('/api/w/' . $webhook2->short_url);
            
        // Verify they are different
        $this->assertNotEquals($webhook1->short_url, $webhook2->short_url);
    }

    public function test_dashboard_shows_copy_buttons_for_short_urls()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            // Check for copy button functionality
            ->assertSee('copyToClipboard')
            ->assertSee('onclick=')
            ->assertSee('/api/w/' . $webhook->short_url)
            ->assertSee('fas fa-copy');
    }

    public function test_dashboard_displays_short_url_only_when_present()
    {
        // Create webhook and manually set short_url to null in database
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        // Manually update to null (bypassing model boot method)
        DB::table('webhook_endpoints')
            ->where('id', $webhook->id)
            ->update(['short_url' => null]);
        
        $webhook->refresh();

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            // Should still show full URL
            ->assertSee('Full:')
            ->assertSee('/api/webhook/' . $webhook->url_path)
            // Should not show short URL section when null
            ->assertDontSee('Short:');
    }

    public function test_dashboard_webhook_table_structure_includes_short_url_column()
    {
        $webhook = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            // Check table headers
            ->assertSee('Name')
            ->assertSee('Webhook URL')
            ->assertSee('Auth Method')
            ->assertSee('Actions')
            // Check webhook data is displayed
            ->assertSee($webhook->name)
            ->assertSee($webhook->slug);
    }

    public function test_dashboard_shows_no_endpoints_message_when_empty()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->get('/dashboard/projects/' . $this->project->id);

        $response->assertStatus(200)
            ->assertSee('No webhook endpoints yet')
            ->assertSee('Create your first webhook endpoint')
            ->assertSee('Create Webhook Endpoint');
    }
}