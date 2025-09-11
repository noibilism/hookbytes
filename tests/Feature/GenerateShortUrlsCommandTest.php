<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateShortUrls;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateShortUrlsCommandTest extends TestCase
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

    public function test_command_generates_short_urls_for_endpoints_without_them()
    {
        // Create endpoints without short URLs
        $endpoint1 = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $endpoint2 = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        // Manually set short_url to null to bypass the boot method
        $endpoint1->update(['short_url' => null]);
        $endpoint2->update(['short_url' => null]);
        
        // Create endpoint with existing short URL
        $endpoint3 = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'short_url' => 'existing1',
        ]);

        $this->artisan('webhook:generate-short-urls')
            ->expectsOutput('Generating short URLs for webhook endpoints...')
            ->expectsOutput('Generated short URLs for 2 webhook endpoints.')
            ->assertExitCode(0);

        // Refresh models
        $endpoint1->refresh();
        $endpoint2->refresh();
        $endpoint3->refresh();

        // Check that short URLs were generated
        $this->assertNotNull($endpoint1->short_url);
        $this->assertNotNull($endpoint2->short_url);
        $this->assertEquals('existing1', $endpoint3->short_url);
        
        // Verify format
        $this->assertEquals(8, strlen($endpoint1->short_url));
        $this->assertEquals(8, strlen($endpoint2->short_url));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $endpoint1->short_url);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $endpoint2->short_url);
        
        // Verify uniqueness
        $this->assertNotEquals($endpoint1->short_url, $endpoint2->short_url);
    }

    public function test_command_with_force_flag_regenerates_existing_short_urls()
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'short_url' => 'existing1',
        ]);

        $originalShortUrl = $endpoint->short_url;

        $this->artisan('webhook:generate-short-urls --force')
            ->expectsOutput('Generating short URLs for webhook endpoints...')
            ->expectsOutput('Generated short URLs for 1 webhook endpoints.')
            ->assertExitCode(0);

        $endpoint->refresh();
        
        $this->assertNotEquals($originalShortUrl, $endpoint->short_url);
        $this->assertEquals(8, strlen($endpoint->short_url));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $endpoint->short_url);
    }

    public function test_command_handles_no_endpoints_gracefully()
    {
        $this->artisan('webhook:generate-short-urls')
            ->expectsOutput('Generating short URLs for webhook endpoints...')
            ->expectsOutput('Generated short URLs for 0 webhook endpoints.')
            ->assertExitCode(0);
    }

    public function test_command_handles_all_endpoints_having_short_urls()
    {
        WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'short_url' => 'existing1',
        ]);
        WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'short_url' => 'existing2',
        ]);
        WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'short_url' => 'existing3',
        ]);

        $this->artisan('webhook:generate-short-urls')
            ->expectsOutput('Generating short URLs for webhook endpoints...')
            ->expectsOutput('Generated short URLs for 0 webhook endpoints.')
            ->assertExitCode(0);
    }

    public function test_command_generates_unique_short_urls_even_with_collisions()
    {
        // Create many endpoints to increase chance of collision testing
        $endpoints = WebhookEndpoint::factory()->count(10)->create([
            'project_id' => $this->project->id,
        ]);
        
        // Manually set short_url to null to bypass the boot method
        $endpoints->each(function ($endpoint) {
            $endpoint->update(['short_url' => null]);
        });

        $this->artisan('webhook:generate-short-urls')
            ->expectsOutput('Generating short URLs for webhook endpoints...')
            ->expectsOutput('Generated short URLs for 10 webhook endpoints.')
            ->assertExitCode(0);

        // Collect all short URLs
        $shortUrls = $endpoints->map(function ($endpoint) {
            $endpoint->refresh();
            return $endpoint->short_url;
        })->toArray();

        // Verify all are unique
        $this->assertEquals(10, count(array_unique($shortUrls)));
        
        // Verify all have correct format
        foreach ($shortUrls as $shortUrl) {
            $this->assertEquals(8, strlen($shortUrl));
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $shortUrl);
        }
    }

    public function test_command_signature_and_description()
    {
        $command = new GenerateShortUrls();
        
        $this->assertEquals('webhook:generate-short-urls', $command->getName());
        $this->assertEquals('Generate short URLs for webhook endpoints that don\'t have them', $command->getDescription());
    }

    public function test_command_shows_progress_for_large_batches()
    {
        // Create many endpoints
        $endpoints = WebhookEndpoint::factory()->count(5)->create([
            'project_id' => $this->project->id,
        ]);
        
        // Manually set short_url to null to bypass the boot method
        $endpoints->each(function ($endpoint) {
            $endpoint->update(['short_url' => null]);
        });

        $this->artisan('webhook:generate-short-urls')
            ->expectsOutput('Generating short URLs for webhook endpoints...')
            ->expectsOutput('Generated short URLs for 5 webhook endpoints.')
            ->assertExitCode(0);
    }
}