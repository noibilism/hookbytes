<?php

namespace Tests\Feature;

use App\Models\WebhookEndpoint;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoints_table_has_short_url_column()
    {
        $this->assertTrue(
            Schema::hasColumn('webhook_endpoints', 'short_url'),
            'webhook_endpoints table should have short_url column'
        );
    }

    public function test_short_url_column_has_correct_properties()
    {
        $columns = Schema::getColumnListing('webhook_endpoints');
        $this->assertContains('short_url', $columns);

        // Test that short_url column accepts string values
        $endpoint = WebhookEndpoint::factory()->create();
        $endpoint->update(['short_url' => 'abc12345']);
        $this->assertEquals('abc12345', $endpoint->fresh()->short_url);
        
        // Test that short_url is automatically generated when creating endpoint
        $newEndpoint = WebhookEndpoint::factory()->create();
        $this->assertNotNull($newEndpoint->short_url);
        $this->assertEquals(8, strlen($newEndpoint->short_url));
    }

    public function test_short_url_column_has_unique_constraint()
    {
        $project = Project::factory()->create();
        
        // Create first endpoint with short URL
        $endpoint1 = WebhookEndpoint::factory()->create([
            'project_id' => $project->id,
            'short_url' => 'unique123'
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Try to create second endpoint with same short URL - should fail
        WebhookEndpoint::factory()->create([
            'project_id' => $project->id,
            'short_url' => 'unique123'
        ]);
    }

    public function test_short_url_column_allows_direct_database_null_values()
    {
        $project = Project::factory()->create();
        
        // Test that we can manually set short_url to null in database
        $endpoint = WebhookEndpoint::factory()->create([
            'project_id' => $project->id
        ]);
        
        // Manually update to null (bypassing model boot method)
         DB::table('webhook_endpoints')
             ->where('id', $endpoint->id)
             ->update(['short_url' => null]);
        
        $endpoint->refresh();
        $this->assertNull($endpoint->short_url);
    }

    public function test_short_url_column_accepts_8_character_strings()
    {
        $project = Project::factory()->create();
        
        // Test 8 character limit (should work)
        $endpoint = WebhookEndpoint::factory()->create([
            'project_id' => $project->id
        ]);
        
        // Update with exactly 8 characters - should work
        $endpoint->update(['short_url' => '12345678']);
        $this->assertEquals('12345678', $endpoint->fresh()->short_url);
        
        // Test with shorter strings
        $endpoint->update(['short_url' => 'abc123']);
        $this->assertEquals('abc123', $endpoint->fresh()->short_url);
        
        // Test with single character
        $endpoint->update(['short_url' => 'x']);
        $this->assertEquals('x', $endpoint->fresh()->short_url);
    }

    public function test_migration_adds_short_url_column_after_url_path()
    {
        $columns = Schema::getColumnListing('webhook_endpoints');
        
        $urlPathIndex = array_search('url_path', $columns);
        $shortUrlIndex = array_search('short_url', $columns);
        
        $this->assertNotFalse($urlPathIndex, 'url_path column should exist');
        $this->assertNotFalse($shortUrlIndex, 'short_url column should exist');
        $this->assertGreaterThan($urlPathIndex, $shortUrlIndex, 'short_url should come after url_path');
    }

    public function test_all_required_webhook_endpoint_columns_exist()
    {
        $requiredColumns = [
            'id',
            'project_id',
            'name',
            'slug',
            'url_path',
            'short_url', // Our new column
            'destination_urls',
            'auth_method',
            'auth_secret',
            'is_active',
            'retry_config',
            'headers_config',
            'created_at',
            'updated_at'
        ];
        
        $actualColumns = Schema::getColumnListing('webhook_endpoints');
        
        foreach ($requiredColumns as $column) {
            $this->assertContains(
                $column,
                $actualColumns,
                "webhook_endpoints table should have {$column} column"
            );
        }
    }
}