<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Project;
use App\Models\WebhookEndpoint;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook {action} 
                            {--project= : Project ID or name}
                            {--endpoint= : Endpoint ID or name}
                            {--event-type= : Event type}
                            {--payload= : JSON payload or file path}
                            {--url= : Destination URL for testing}
                            {--event-id= : Event ID for replay}
                            {--format=table : Output format (table, json)}
                            {--limit=10 : Limit number of results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'HookBytes CLI tool for webhook testing and management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'test':
                return $this->testWebhook();
            case 'send':
                return $this->sendEvent();
            case 'replay':
                return $this->replayEvent();
            case 'list':
                return $this->listEvents();
            case 'status':
                return $this->checkStatus();
            case 'projects':
                return $this->listProjects();
            case 'endpoints':
                return $this->listEndpoints();
            case 'tunnel':
                return $this->startTunnel();
            default:
                $this->error("Unknown action: {$action}");
                $this->showHelp();
                return 1;
        }
    }

    /**
     * Test a webhook URL directly
     */
    private function testWebhook()
    {
        $url = $this->option('url');
        if (!$url) {
            $url = $this->ask('Enter webhook URL to test');
        }

        $payload = $this->getPayload() ?: [
            'event_type' => 'test.webhook',
            'timestamp' => now()->toISOString(),
            'data' => ['message' => 'Test webhook from HookBytes CLI']
        ];

        $this->info("Testing webhook: {$url}");
        $this->line('Payload: ' . json_encode($payload, JSON_PRETTY_PRINT));

        $startTime = microtime(true);
        
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HookBytes-CLI/1.0',
                    'X-HookBytes-Test' => 'true',
                ])
                ->post($url, $payload);

            $responseTime = round((microtime(true) - $startTime) * 1000);

            $this->newLine();
            $this->info("✅ Response received in {$responseTime}ms");
            $this->line("Status: {$response->status()}");
            $this->line("Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT));
            
            if ($response->body()) {
                $this->line("Body: {$response->body()}");
            }

            return $response->successful() ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("❌ Request failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Send an event through the webhook system
     */
    private function sendEvent()
    {
        $project = $this->getProject();
        $endpoint = $this->getEndpoint($project);
        $eventType = $this->option('event-type') ?: $this->ask('Event type', 'test.event');
        $payload = $this->getPayload() ?: ['message' => 'Test event from CLI'];

        $event = Event::create([
            'event_id' => 'cli_' . Str::uuid(),
            'project_id' => $project->id,
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => $eventType,
            'payload' => json_encode($payload),
            'status' => 'pending',
        ]);

        ProcessWebhookEvent::dispatch($event);

        $this->info("✅ Event created and queued for processing");
        $this->line("Event ID: {$event->event_id}");
        $this->line("Project: {$project->name}");
        $this->line("Endpoint: {$endpoint->name}");
        $this->line("Type: {$eventType}");

        return 0;
    }

    /**
     * Replay an existing event
     */
    private function replayEvent()
    {
        $eventId = $this->option('event-id');
        if (!$eventId) {
            $eventId = $this->ask('Enter event ID to replay');
        }

        $event = Event::where('event_id', $eventId)->first();
        if (!$event) {
            $this->error("Event not found: {$eventId}");
            return 1;
        }

        $newEvent = Event::create([
            'event_id' => 'replay_' . Str::uuid(),
            'project_id' => $event->project_id,
            'webhook_endpoint_id' => $event->webhook_endpoint_id,
            'event_type' => $event->event_type,
            'payload' => $event->payload,
            'status' => 'pending',
        ]);

        ProcessWebhookEvent::dispatch($newEvent);

        $this->info("✅ Event replayed successfully");
        $this->line("Original Event ID: {$event->event_id}");
        $this->line("New Event ID: {$newEvent->event_id}");

        return 0;
    }

    /**
     * List recent events
     */
    private function listEvents()
    {
        $limit = $this->option('limit');
        $project = $this->option('project') ? $this->getProject() : null;
        
        $query = Event::with(['project', 'webhookEndpoint'])->latest();
        
        if ($project) {
            $query->where('project_id', $project->id);
        }
        
        $events = $query->limit($limit)->get();

        if ($this->option('format') === 'json') {
            $this->line(json_encode($events->toArray(), JSON_PRETTY_PRINT));
            return 0;
        }

        $headers = ['Event ID', 'Type', 'Project', 'Endpoint', 'Status', 'Created'];
        $rows = $events->map(function ($event) {
            return [
                $event->event_id,
                $event->event_type,
                $event->project->name,
                $event->webhookEndpoint->name,
                $event->status,
                $event->created_at->format('M j, H:i:s'),
            ];
        });

        $this->table($headers, $rows);
        return 0;
    }

    /**
     * Check system status
     */
    private function checkStatus()
    {
        $stats = [
            'Total Events' => Event::count(),
            'Pending Events' => Event::where('status', 'pending')->count(),
            'Failed Events' => Event::where('status', 'failed')->count(),
            'Projects' => Project::count(),
            'Endpoints' => WebhookEndpoint::count(),
        ];

        $this->info('🚀 HookBytes System Status');
        $this->newLine();
        
        foreach ($stats as $label => $value) {
            $this->line("  {$label}: {$value}");
        }

        return 0;
    }

    /**
     * List projects
     */
    private function listProjects()
    {
        $projects = Project::withCount(['events', 'webhookEndpoints'])->get();

        $headers = ['ID', 'Name', 'Description', 'Events', 'Endpoints', 'Created'];
        $rows = $projects->map(function ($project) {
            return [
                $project->id,
                $project->name,
                Str::limit($project->description, 30),
                $project->events_count,
                $project->webhook_endpoints_count,
                $project->created_at->format('M j, Y'),
            ];
        });

        $this->table($headers, $rows);
        return 0;
    }

    /**
     * List endpoints
     */
    private function listEndpoints()
    {
        $project = $this->option('project') ? $this->getProject() : null;
        
        $query = WebhookEndpoint::with(['project', 'destinationUrls']);
        
        if ($project) {
            $query->where('project_id', $project->id);
        }
        
        $endpoints = $query->get();

        $headers = ['ID', 'Name', 'Project', 'URLs', 'Active', 'Created'];
        $rows = $endpoints->map(function ($endpoint) {
            return [
                $endpoint->id,
                $endpoint->name,
                $endpoint->project->name,
                $endpoint->destinationUrls->count(),
                $endpoint->is_active ? '✅' : '❌',
                $endpoint->created_at->format('M j, Y'),
            ];
        });

        $this->table($headers, $rows);
        return 0;
    }

    /**
     * Start a local tunnel (placeholder)
     */
    private function startTunnel()
    {
        $this->info('🚇 Starting local tunnel...');
        $this->warn('Tunnel functionality requires ngrok or similar tool.');
        $this->line('Install ngrok: https://ngrok.com/download');
        $this->line('Then run: ngrok http 8000');
        
        return 0;
    }

    /**
     * Get project by ID or name
     */
    private function getProject()
    {
        $projectOption = $this->option('project');
        
        if ($projectOption) {
            $project = is_numeric($projectOption) 
                ? Project::find($projectOption)
                : Project::where('name', $projectOption)->first();
                
            if (!$project) {
                $this->error("Project not found: {$projectOption}");
                exit(1);
            }
            
            return $project;
        }

        $projects = Project::all();
        if ($projects->isEmpty()) {
            $this->error('No projects found. Create a project first.');
            exit(1);
        }

        $choices = $projects->pluck('name', 'id')->toArray();
        $projectId = $this->choice('Select a project', $choices);
        
        return Project::find(array_search($projectId, $choices));
    }

    /**
     * Get endpoint by ID or name
     */
    private function getEndpoint($project)
    {
        $endpointOption = $this->option('endpoint');
        
        if ($endpointOption) {
            $endpoint = is_numeric($endpointOption)
                ? WebhookEndpoint::find($endpointOption)
                : WebhookEndpoint::where('name', $endpointOption)->where('project_id', $project->id)->first();
                
            if (!$endpoint) {
                $this->error("Endpoint not found: {$endpointOption}");
                exit(1);
            }
            
            return $endpoint;
        }

        $endpoints = $project->webhookEndpoints;
        if ($endpoints->isEmpty()) {
            $this->error('No endpoints found for this project.');
            exit(1);
        }

        $choices = $endpoints->pluck('name', 'id')->toArray();
        $endpointId = $this->choice('Select an endpoint', $choices);
        
        return WebhookEndpoint::find(array_search($endpointId, $choices));
    }

    /**
     * Get payload from option or file
     */
    private function getPayload()
    {
        $payloadOption = $this->option('payload');
        
        if (!$payloadOption) {
            return null;
        }

        // Check if it's a file path
        if (file_exists($payloadOption)) {
            $content = file_get_contents($payloadOption);
            return json_decode($content, true);
        }

        // Try to parse as JSON
        return json_decode($payloadOption, true);
    }

    /**
     * Show help information
     */
    private function showHelp()
    {
        $this->info('🪝 HookBytes CLI Tool');
        $this->newLine();
        $this->line('Available actions:');
        $this->line('  test      - Test a webhook URL directly');
        $this->line('  send      - Send an event through the system');
        $this->line('  replay    - Replay an existing event');
        $this->line('  list      - List recent events');
        $this->line('  status    - Show system status');
        $this->line('  projects  - List all projects');
        $this->line('  endpoints - List webhook endpoints');
        $this->line('  tunnel    - Start local tunnel (requires ngrok)');
        $this->newLine();
        $this->line('Examples:');
        $this->line('  php artisan webhook test --url=https://httpbin.org/post');
        $this->line('  php artisan webhook send --project=1 --event-type=user.created');
        $this->line('  php artisan webhook replay --event-id=abc123');
        $this->line('  php artisan webhook list --project=myproject --limit=5');
    }
}
