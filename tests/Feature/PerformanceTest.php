<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $webhookEndpoint;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['is_active' => true]);
        $this->webhookEndpoint = WebhookEndpoint::factory()->create([
            'project_id' => $this->project->id,
            'destination_urls' => ['https://example.com/webhook'],
            'auth_method' => 'none',
            'is_active' => true,
        ]);
    }

    // ========== Webhook Ingestion Performance ==========

    /** @test */
    public function webhook_ingestion_handles_concurrent_requests()
    {
        Queue::fake();
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200)
        ]);

        $startTime = microtime(true);
        $responses = [];
        
        // Simulate 50 concurrent webhook requests
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
                'event_id' => $i,
                'timestamp' => now()->toISOString(),
                'data' => [
                    'user_id' => rand(1, 1000),
                    'action' => 'test_action_' . $i
                ]
            ]);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        // Should handle 50 requests in reasonable time (under 5 seconds)
        $this->assertLessThan(5.0, $totalTime, 'Webhook ingestion took too long: ' . $totalTime . ' seconds');
        
        // Verify all events were created
        $this->assertEquals(50, Event::count());
        
        // Calculate average response time
        $avgResponseTime = $totalTime / 50;
        $this->assertLessThan(0.1, $avgResponseTime, 'Average response time too high: ' . $avgResponseTime . ' seconds');
    }

    /** @test */
    public function webhook_ingestion_handles_large_payloads_efficiently()
    {
        Queue::fake();
        
        // Create progressively larger payloads
        $payloadSizes = [1024, 10240, 102400, 512000]; // 1KB, 10KB, 100KB, 500KB
        $responseTimes = [];
        
        foreach ($payloadSizes as $size) {
            $largeData = str_repeat('A', $size);
            
            $startTime = microtime(true);
            
            $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
                'large_field' => $largeData,
                'metadata' => [
                    'size' => $size,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;
            $responseTimes[$size] = $responseTime;
            
            $response->assertStatus(200);
            
            // Response time should scale reasonably with payload size
            $this->assertLessThan(2.0, $responseTime, "Response time for {$size} bytes too high: {$responseTime} seconds");
        }
        
        // Verify events were created with correct payload sizes
        $events = Event::orderBy('created_at')->get();
        $this->assertCount(4, $events);
        
        foreach ($events as $index => $event) {
            $expectedSize = $payloadSizes[$index];
            $this->assertEquals($expectedSize, $event->payload['metadata']['size']);
        }
    }

    /** @test */
    public function webhook_ingestion_maintains_performance_under_sustained_load()
    {
        Queue::fake();
        
        $batchSize = 20;
        $numBatches = 5;
        $batchTimes = [];
        
        for ($batch = 0; $batch < $numBatches; $batch++) {
            $startTime = microtime(true);
            
            for ($i = 0; $i < $batchSize; $i++) {
                $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
                    'batch' => $batch,
                    'item' => $i,
                    'timestamp' => now()->toISOString()
                ]);
                $response->assertStatus(200);
            }
            
            $endTime = microtime(true);
            $batchTime = $endTime - $startTime;
            $batchTimes[] = $batchTime;
            
            // Small delay between batches to simulate real-world usage
            usleep(100000); // 100ms
        }
        
        // Performance should remain consistent across batches
        $avgTime = array_sum($batchTimes) / count($batchTimes);
        $maxTime = max($batchTimes);
        $minTime = min($batchTimes);
        
        // Max time shouldn't be more than 50% higher than average
        $this->assertLessThan($avgTime * 1.5, $maxTime, 'Performance degraded significantly under sustained load');
        
        // Verify all events were created
        $this->assertEquals($batchSize * $numBatches, Event::count());
    }

    // ========== Database Performance ==========

    /** @test */
    public function database_queries_are_optimized_for_event_creation()
    {
        Queue::fake();
        
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
            'test' => 'query_optimization'
        ]);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        $response->assertStatus(200);
        
        // Should use minimal number of queries for event creation
        $this->assertLessThanOrEqual(5, count($queries), 'Too many database queries for event creation');
        
        // Check for N+1 query problems
        $selectQueries = array_filter($queries, function ($query) {
            return strpos(strtolower($query['query']), 'select') === 0;
        });
        
        $this->assertLessThanOrEqual(3, count($selectQueries), 'Potential N+1 query problem detected');
    }

    /** @test */
    public function event_listing_performance_with_large_dataset()
    {
        // Create a large number of events
        $eventCount = 1000;
        $events = [];
        
        for ($i = 0; $i < $eventCount; $i++) {
            $events[] = [
                'webhook_endpoint_id' => $this->webhookEndpoint->id,
                'project_id' => $this->project->id,
                'payload' => json_encode(['event_number' => $i]),
                'headers' => json_encode(['x-event-id' => $i]),
                'status' => ['pending', 'delivered', 'failed'][rand(0, 2)],
                'attempt_count' => rand(0, 3),
                'created_at' => now()->subMinutes(rand(0, 1440)), // Random time in last 24 hours
                'updated_at' => now()->subMinutes(rand(0, 1440)),
            ];
        }
        
        // Insert in chunks for better performance
        $chunks = array_chunk($events, 100);
        foreach ($chunks as $chunk) {
            Event::insert($chunk);
        }
        
        // Test API endpoint performance
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/v1/events?per_page=50', [
            'Authorization' => 'Bearer ' . $this->project->api_key
        ]);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        $this->assertCount(50, $response->json('data'));
        
        // Should respond quickly even with large dataset
        $this->assertLessThan(1.0, $responseTime, 'Event listing too slow with large dataset: ' . $responseTime . ' seconds');
        
        // Test with filtering
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/v1/events?status=delivered&per_page=25', [
            'Authorization' => 'Bearer ' . $this->project->api_key
        ]);
        
        $endTime = microtime(true);
        $filterResponseTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        
        // Filtered queries should also be fast
        $this->assertLessThan(1.0, $filterResponseTime, 'Filtered event listing too slow: ' . $filterResponseTime . ' seconds');
    }

    /** @test */
    public function dashboard_performance_with_large_dataset()
    {
        // Create multiple projects and endpoints
        $projects = Project::factory()->count(10)->create();
        $endpoints = [];
        
        foreach ($projects as $project) {
            $endpoints = array_merge($endpoints, WebhookEndpoint::factory()->count(5)->create([
                'project_id' => $project->id
            ])->toArray());
        }
        
        // Create events for each endpoint
        foreach ($endpoints as $endpoint) {
            Event::factory()->count(20)->create([
                'webhook_endpoint_id' => $endpoint['id'],
                'project_id' => $endpoint['project_id']
            ]);
        }
        
        // Test dashboard loading performance
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user)
            ->get('/dashboard');
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        
        // Dashboard should load quickly even with lots of data
        $this->assertLessThan(2.0, $responseTime, 'Dashboard loading too slow: ' . $responseTime . ' seconds');
    }

    // ========== Queue Performance ==========

    /** @test */
    public function webhook_processing_queue_performance()
    {
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200, [], 100) // 100ms delay
        ]);
        
        // Create multiple events to process
        $eventCount = 20;
        $events = Event::factory()->count($eventCount)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'pending'
        ]);
        
        $startTime = microtime(true);
        
        // Process all events
        foreach ($events as $event) {
            $job = new ProcessWebhookEvent($event);
            $job->handle();
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Verify all events were processed
        $processedEvents = Event::where('status', 'delivered')->count();
        $this->assertEquals($eventCount, $processedEvents);
        
        // Calculate average processing time per event
        $avgProcessingTime = $totalTime / $eventCount;
        $this->assertLessThan(0.5, $avgProcessingTime, 'Average event processing time too high: ' . $avgProcessingTime . ' seconds');
    }

    /** @test */
    public function bulk_event_replay_performance()
    {
        Queue::fake();
        
        // Create failed events for bulk replay
        $eventCount = 100;
        $events = Event::factory()->count($eventCount)->create([
            'webhook_endpoint_id' => $this->webhookEndpoint->id,
            'project_id' => $this->project->id,
            'status' => 'failed'
        ]);
        
        $eventIds = $events->pluck('id')->toArray();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->post('/dashboard/events/bulk-retry', [
                'event_ids' => $eventIds,
                '_token' => 'test-token'
            ]);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        $response->assertRedirect();
        
        // Bulk operation should complete quickly
        $this->assertLessThan(3.0, $responseTime, 'Bulk replay operation too slow: ' . $responseTime . ' seconds');
        
        // Verify events were queued for replay
        $pendingEvents = Event::where('status', 'pending')->count();
        $this->assertEquals($eventCount, $pendingEvents);
    }

    // ========== Memory Usage Tests ==========

    /** @test */
    public function memory_usage_remains_stable_under_load()
    {
        Queue::fake();
        
        $initialMemory = memory_get_usage(true);
        $memoryReadings = [];
        
        // Process multiple batches and monitor memory
        for ($batch = 0; $batch < 10; $batch++) {
            for ($i = 0; $i < 20; $i++) {
                $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
                    'batch' => $batch,
                    'item' => $i,
                    'large_data' => str_repeat('X', 1000) // 1KB per request
                ]);
            }
            
            $currentMemory = memory_get_usage(true);
            $memoryReadings[] = $currentMemory;
            
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage increased too much: ' . ($memoryIncrease / 1024 / 1024) . ' MB');
        
        // Memory usage should not continuously grow
        $lastFiveReadings = array_slice($memoryReadings, -5);
        $avgLastFive = array_sum($lastFiveReadings) / count($lastFiveReadings);
        $maxLastFive = max($lastFiveReadings);
        
        $this->assertLessThan($avgLastFive * 1.2, $maxLastFive, 'Memory usage appears to be continuously growing');
    }

    // ========== Caching Performance ==========

    /** @test */
    public function webhook_endpoint_lookup_uses_caching_effectively()
    {
        Cache::flush();
        
        // First request - should hit database
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/webhook/' . $this->webhookEndpoint->url_path . '/info');
        $firstRequestTime = microtime(true) - $startTime;
        
        $response1->assertStatus(200);
        
        // Second request - should use cache
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/webhook/' . $this->webhookEndpoint->url_path . '/info');
        $secondRequestTime = microtime(true) - $startTime;
        
        $response2->assertStatus(200);
        
        // Cached request should be significantly faster
        $this->assertLessThan($firstRequestTime * 0.5, $secondRequestTime, 'Caching not providing expected performance improvement');
        
        // Responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    // ========== Stress Testing ==========

    /** @test */
    public function system_handles_burst_traffic_gracefully()
    {
        Queue::fake();
        
        $burstSize = 100;
        $responses = [];
        $errors = [];
        
        // Simulate burst traffic
        $startTime = microtime(true);
        
        for ($i = 0; $i < $burstSize; $i++) {
            try {
                $response = $this->postJson('/api/webhook/' . $this->webhookEndpoint->url_path, [
                    'burst_id' => $i,
                    'timestamp' => microtime(true)
                ]);
                $responses[] = $response->getStatusCode();
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Most requests should succeed (allow for some rate limiting)
        $successCount = count(array_filter($responses, function ($status) {
            return $status === 200;
        }));
        
        $this->assertGreaterThan($burstSize * 0.8, $successCount, 'Too many requests failed during burst traffic');
        
        // System should handle burst in reasonable time
        $this->assertLessThan(10.0, $totalTime, 'Burst traffic handling took too long: ' . $totalTime . ' seconds');
        
        // Should not have any fatal errors
        $this->assertEmpty($errors, 'Fatal errors occurred during burst traffic: ' . implode(', ', $errors));
    }

    /** @test */
    public function database_connection_pooling_performance()
    {
        // Test multiple concurrent database operations
        $operations = [];
        $startTime = microtime(true);
        
        // Simulate concurrent database-heavy operations
        for ($i = 0; $i < 20; $i++) {
            // Create endpoint
            $endpoint = WebhookEndpoint::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Performance Test Endpoint ' . $i
            ]);
            
            // Create events
            Event::factory()->count(5)->create([
                'webhook_endpoint_id' => $endpoint->id,
                'project_id' => $this->project->id
            ]);
            
            // Query events
            $events = Event::where('webhook_endpoint_id', $endpoint->id)->get();
            $operations[] = count($events);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // All operations should complete successfully
        $this->assertCount(20, $operations);
        $this->assertEquals(5, $operations[0]); // Each endpoint should have 5 events
        
        // Should complete in reasonable time
        $this->assertLessThan(5.0, $totalTime, 'Database operations took too long: ' . $totalTime . ' seconds');
    }
}