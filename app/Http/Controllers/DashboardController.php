<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventDelivery;
use App\Models\Project;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index(Request $request)
    {
        $projects = Project::withCount(['events', 'webhookEndpoints'])->get();
        
        // Get recent events with stats (paginated to 5 per page)
        $recentEvents = Event::with(['project', 'webhookEndpoint', 'deliveries'])
            ->latest()
            ->paginate(5);

        // Get delivery stats for the last 24 hours
        $stats = $this->getDeliveryStats();

        return view('dashboard.index', compact('projects', 'recentEvents', 'stats'));
    }

    /**
     * Display events with search and filtering
     */
    public function events(Request $request)
    {
        $query = Event::with(['project', 'webhookEndpoint', 'deliveries'])
            ->latest();

        // Apply filters
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('endpoint_id')) {
            $query->where('webhook_endpoint_id', $request->endpoint_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', 'like', '%' . $request->event_type . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Search in payload
        if ($request->filled('search')) {
            $query->where('payload', 'like', '%' . $request->search . '%');
        }

        $events = $query->paginate(20)->withQueryString();
        $projects = Project::all();
        $endpoints = WebhookEndpoint::all();

        return view('dashboard.events', compact('events', 'projects', 'endpoints'));
    }

    /**
     * Show event details
     */
    public function showEvent(Event $event)
    {
        $event->load(['project', 'webhookEndpoint', 'deliveries']);
        
        return view('dashboard.event-details', compact('event'));
    }

    /**
     * Replay an event
     */
    public function replayEvent(Event $event)
    {
        // Create a new event with the same payload
        $newEvent = Event::create([
            'event_id' => \Illuminate\Support\Str::uuid(),
            'project_id' => $event->project_id,
            'webhook_endpoint_id' => $event->webhook_endpoint_id,
            'event_type' => $event->event_type,
            'payload' => $event->payload,
            'headers' => $event->headers,
            'status' => 'pending',
        ]);

        // Dispatch the job
        ProcessWebhookEvent::dispatch($newEvent);

        return response()->json([
            'success' => true,
            'message' => 'Event replayed successfully',
            'new_event_id' => $newEvent->event_id,
        ]);
    }

    /**
     * Get delivery statistics
     */
    private function getDeliveryStats()
    {
        $last24Hours = Carbon::now()->subDay();

        return [
            'total_events' => Event::where('created_at', '>=', $last24Hours)->count(),
            'successful_deliveries' => EventDelivery::where('created_at', '>=', $last24Hours)
                ->where('status', 'success')->count(),
            'failed_deliveries' => EventDelivery::where('created_at', '>=', $last24Hours)
                ->where('status', 'failed')->count(),
            'pending_events' => Event::where('status', 'pending')->count(),
            'average_response_time' => EventDelivery::where('created_at', '>=', $last24Hours)
                ->where('status', 'success')
                ->avg('latency_ms') ?? 0,
        ];
    }

    /**
     * Get events data for charts (API endpoint)
     */
    public function eventsChart(Request $request)
    {
        $days = $request->get('days');
        $hours = $request->get('hours');
        $today = $request->get('today');
        
        if ($today) {
            // Today's hourly data
            $startDate = Carbon::today();
            $endDate = Carbon::tomorrow();
            
            $rawData = Event::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
            
            // Transform data for Highcharts
            $categories = [];
            $totalData = [];
            $successfulData = [];
            $failedData = [];
            
            // Create 24-hour structure
            $hourlyData = [];
            foreach ($rawData as $item) {
                $hourlyData[$item->hour] = $item;
            }
            
            for ($i = 0; $i < 24; $i++) {
                $categories[] = sprintf('%02d:00', $i);
                if (isset($hourlyData[$i])) {
                    $totalData[] = (int) $hourlyData[$i]->total;
                    $successfulData[] = (int) $hourlyData[$i]->successful;
                    $failedData[] = (int) $hourlyData[$i]->failed;
                } else {
                    $totalData[] = 0;
                    $successfulData[] = 0;
                    $failedData[] = 0;
                }
            }
        } elseif ($hours) {
            // Hourly data
            $startDate = Carbon::now()->subHours($hours);
            
            $rawData = Event::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
            
            // Transform data for Highcharts
            $categories = [];
            $totalData = [];
            $successfulData = [];
            $failedData = [];
            
            // If no data, create sample data for the last hours
            if ($rawData->isEmpty()) {
                for ($i = $hours - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subHours($i);
                    $categories[] = $date->format('H:00');
                    $totalData[] = 0;
                    $successfulData[] = 0;
                    $failedData[] = 0;
                }
            } else {
                foreach ($rawData as $item) {
                    $categories[] = Carbon::parse($item->hour)->format('H:00');
                    $totalData[] = (int) $item->total;
                    $successfulData[] = (int) $item->successful;
                    $failedData[] = (int) $item->failed;
                }
            }
        } else {
            // Daily data (default)
            $days = $days ?: 7;
            $startDate = Carbon::now()->subDays($days);
            
            $rawData = Event::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
            // Transform data for Highcharts
            $categories = [];
            $totalData = [];
            $successfulData = [];
            $failedData = [];
            
            // If no data, create sample data for the last days
            if ($rawData->isEmpty()) {
                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $categories[] = $date->format('M j');
                    $totalData[] = 0;
                    $successfulData[] = 0;
                    $failedData[] = 0;
                }
            } else {
                foreach ($rawData as $item) {
                    $categories[] = Carbon::parse($item->date)->format('M j');
                    $totalData[] = (int) $item->total;
                    $successfulData[] = (int) $item->successful;
                    $failedData[] = (int) $item->failed;
                }
            }
        }

        return response()->json([
            'categories' => $categories,
            'total' => $totalData,
            'successful' => $successfulData,
            'failed' => $failedData
        ]);
    }

    /**
     * Get project-specific events data for charts (API endpoint)
     */
    public function projectEventsChart(Request $request, $projectId)
    {
        $startDate = Carbon::today();
        $endDate = Carbon::tomorrow();
        
        $rawData = Event::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as successful'),
            DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
        )
        ->where('project_id', $projectId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();
        
        // Transform data for Highcharts
        $categories = [];
        $totalData = [];
        $successfulData = [];
        $failedData = [];
        
        // Create 24-hour structure
        $hourlyData = [];
        foreach ($rawData as $item) {
            $hourlyData[$item->hour] = $item;
        }
        
        for ($i = 0; $i < 24; $i++) {
            $categories[] = sprintf('%02d:00', $i);
            if (isset($hourlyData[$i])) {
                $totalData[] = (int) $hourlyData[$i]->total;
                $successfulData[] = (int) $hourlyData[$i]->successful;
                $failedData[] = (int) $hourlyData[$i]->failed;
            } else {
                $totalData[] = 0;
                $successfulData[] = 0;
                $failedData[] = 0;
            }
        }
        
        return response()->json([
            'categories' => $categories,
            'total' => $totalData,
            'successful' => $successfulData,
            'failed' => $failedData
        ]);
    }

    /**
     * Show all projects
     */
    public function projects()
    {
        $projects = Project::withCount(['events', 'webhookEndpoints'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.projects.index', compact('projects'));
    }

    /**
     * Show create project form
     */
    public function createProject()
    {
        return view('dashboard.projects.create');
    }

    /**
     * Store a new project
     */
    public function storeProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'api_key' => Str::random(32),
            'webhook_secret' => Str::random(32),
        ]);

        return redirect()->route('dashboard.projects.show', $project)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Show project details
     */
    public function showProject(Project $project)
    {
        $project->load(['webhookEndpoints', 'events' => function($query) {
            $query->latest()->limit(10);
        }]);

        // Make API credentials visible for the project details view
        $project->makeVisible(['api_key', 'webhook_secret']);

        return view('dashboard.projects.show', compact('project'));
    }

    /**
     * Show edit project form
     */
    public function editProject(Project $project)
    {
        return view('dashboard.projects.edit', compact('project'));
    }

    /**
     * Update project
     */
    public function updateProject(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('dashboard.projects.show', $project)
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Show create endpoint form
     */
    public function createEndpoint(Project $project)
    {
        return view('dashboard.endpoints.create', compact('project'));
    }

    /**
     * Store a new webhook endpoint
     */
    public function storeEndpoint(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'destination_urls' => 'required|array|min:1',
            'destination_urls.*' => 'required|url',
            'auth_method' => 'required|in:hmac,shared_secret,none',
            'retry_config' => 'nullable|array',
            'headers_config' => 'nullable|array',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $counter = 1;
        
        // Ensure slug uniqueness within the project
        while ($project->webhookEndpoints()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        $urlPath = $project->slug . '/' . $slug;

        $endpoint = $project->webhookEndpoints()->create([
            'name' => $request->name,
            'slug' => $slug,
            'url_path' => $urlPath,
            'destination_urls' => $request->destination_urls,
            'auth_method' => $request->auth_method,
            'auth_secret' => $request->auth_method !== 'none' ? Str::random(32) : null,
            'retry_config' => $request->retry_config,
            'headers_config' => $request->headers_config,
        ]);

        return redirect()->route('dashboard.endpoints.create', $project)
            ->with('success', 'Webhook endpoint created successfully!')
            ->with('created_endpoint', $endpoint);
    }

    /**
     * Show edit endpoint form
     */
    public function editEndpoint(WebhookEndpoint $endpoint)
    {
        return view('dashboard.endpoints.edit', compact('endpoint'));
    }

    /**
     * Update webhook endpoint
     */
    public function updateEndpoint(Request $request, WebhookEndpoint $endpoint)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'destination_urls' => 'required|array|min:1',
            'destination_urls.*' => 'required|url',
            'auth_method' => 'required|in:hmac,shared_secret,none',
            'is_active' => 'boolean',
            'header_keys' => 'nullable|array',
            'header_values' => 'nullable|array',
        ]);

        // Process headers configuration
        $headersConfig = [];
        if ($request->header_keys && $request->header_values) {
            $keys = array_filter($request->header_keys);
            $values = array_filter($request->header_values);
            
            foreach ($keys as $index => $key) {
                if (isset($values[$index]) && !empty(trim($key)) && !empty(trim($values[$index]))) {
                    $headersConfig[trim($key)] = trim($values[$index]);
                }
            }
        }

        $endpoint->update([
            'name' => $request->name,
            'destination_urls' => $request->destination_urls,
            'auth_method' => $request->auth_method,
            'auth_secret' => $request->auth_method !== 'none' ? ($endpoint->auth_secret ?: Str::random(32)) : null,
            'is_active' => $request->boolean('is_active', true),
            'headers_config' => $headersConfig,
        ]);

        return redirect()->route('dashboard.projects.show', $endpoint->project)
            ->with('success', 'Webhook endpoint updated successfully!');
    }

    /**
     * Delete webhook endpoint
     */
    public function deleteEndpoint(WebhookEndpoint $endpoint)
    {
        $project = $endpoint->project;
        $endpoint->delete();

        return redirect()->route('dashboard.projects.show', $project)
            ->with('success', 'Webhook endpoint deleted successfully!');
    }

    /**
     * Delete project
     */
    public function destroyProject(Project $project)
    {
        $project->delete();

        return redirect()->route('dashboard.projects')
            ->with('success', 'Project deleted successfully!');
    }

    /**
     * Bulk retry events
     */
    public function bulkRetryEvents(Request $request)
    {
        $request->validate([
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'required|integer|exists:events,id'
        ]);

        $eventIds = $request->event_ids;
        $retriedCount = 0;

        try {
            DB::beginTransaction();

            foreach ($eventIds as $eventId) {
                $originalEvent = Event::find($eventId);
                
                if (!$originalEvent) {
                    continue;
                }

                // Create a new event for retry
                $newEvent = Event::create([
                    'project_id' => $originalEvent->project_id,
                    'webhook_endpoint_id' => $originalEvent->webhook_endpoint_id,
                    'event_type' => $originalEvent->event_type,
                    'payload' => $originalEvent->payload,
                    'headers' => $originalEvent->headers,
                    'signature' => $originalEvent->signature,
                    'status' => 'pending',
                ]);

                // Dispatch the webhook processing job
                ProcessWebhookEvent::dispatch($newEvent);
                $retriedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'retried_count' => $retriedCount,
                'message' => "Successfully retried {$retriedCount} event" . ($retriedCount > 1 ? 's' : '')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function testEndpoint(Request $request, WebhookEndpoint $endpoint)
    {
        $request->validate([
            'payload' => 'required|array',
            'headers' => 'nullable|array',
        ]);

        try {
            // Create a test event
            $testEvent = Event::create([
                'project_id' => $endpoint->project_id,
                'webhook_endpoint_id' => $endpoint->id,
                'event_type' => 'test',
                'payload' => $request->payload,
                'headers' => $request->headers ?? [],
                'status' => 'pending',
            ]);

            // Dispatch the webhook processing job
            ProcessWebhookEvent::dispatch($testEvent);

            return response()->json([
                'success' => true,
                'message' => 'Test webhook sent successfully',
                'event_id' => $testEvent->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show endpoint events
     */
    public function endpointEvents(Request $request, WebhookEndpoint $endpoint)
    {
        $query = Event::where('webhook_endpoint_id', $endpoint->id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $events = $query->get();

        return view('dashboard.endpoints.events', compact('endpoint', 'events'));
    }

    /**
     * Display API documentation
     */
    public function apiDocs()
    {
        return view('dashboard.api-docs');
    }
}
