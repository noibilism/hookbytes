<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <i class="fas fa-book mr-2"></i>{{ __('API Documentation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Introduction -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold mb-4">HookBytes Webhook Management API</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            The HookBytes API allows you to programmatically manage webhook endpoints, retrieve events, and replay failed deliveries. 
                            All API endpoints require authentication using your project's API key.
                        </p>
                        
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">
                                <i class="fas fa-key mr-2"></i>Authentication
                            </h4>
                            <p class="text-blue-700 dark:text-blue-300 mb-2">
                                Include your API key in the request headers:
                            </p>
                            <code class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-sm">
                                X-API-Key: your-project-api-key
                            </code>
                            <p class="text-blue-700 dark:text-blue-300 mt-2 text-sm">
                                You can find your API key in your project settings.
                            </p>
                        </div>
                    </div>

                    <!-- Base URL -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold mb-2">Base URL</h4>
                        <code class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-2 rounded block">
                            {{ url('/api/v1') }}
                        </code>
                    </div>

                    <!-- Endpoints -->
                    <div class="space-y-8">
                        
                        <!-- Create Webhook Endpoint -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 rounded-full text-sm font-medium mr-3">
                                    POST
                                </span>
                                <h4 class="text-lg font-semibold">/webhooks/endpoints</h4>
                            </div>
                            
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Create a new webhook endpoint for your project.
                            </p>
                            
                            <h5 class="font-semibold mb-2">Request Body:</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm mb-4"><code>{
  "name": "My Webhook Endpoint",
  "destination_urls": ["https://your-app.com/webhook"],
  "auth_method": "hmac", // "none", "hmac", or "shared_secret"
  "auth_secret": "your-secret-key", // required unless auth_method is "none"
  "retry_config": { // optional
    "max_attempts": 3,
    "retry_delay": 60,
    "backoff_multiplier": 2
  },
  "headers_config": {}, // optional custom headers
  "is_active": true // optional, defaults to true
}</code></pre>
                            
                            <h5 class="font-semibold mb-2">Response (201 Created):</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm"><code>{
  "success": true,
  "data": {
    "id": 1,
    "name": "My Webhook Endpoint",
    "slug": "my-webhook-endpoint",
    "url_path": "/webhook/project-slug/my-webhook-endpoint",
    "webhook_url": "{{ url('/api/webhook/project-slug/my-webhook-endpoint') }}",
    "destination_urls": ["https://your-app.com/webhook"],
    "auth_method": "hmac",
    "is_active": true,
    "created_at": "2025-01-01T00:00:00.000000Z"
  }
}</code></pre>
                        </div>

                        <!-- Get Events -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium mr-3">
                                    GET
                                </span>
                                <h4 class="text-lg font-semibold">/events</h4>
                            </div>
                            
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Retrieve events for your project with optional filtering and pagination.
                            </p>
                            
                            <h5 class="font-semibold mb-2">Query Parameters:</h5>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                                <ul class="space-y-2 text-sm">
                                    <li><code class="text-blue-600 dark:text-blue-400">status</code> - Filter by event status: pending, processing, delivered, failed</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">event_type</code> - Filter by event type</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">webhook_endpoint_id</code> - Filter by webhook endpoint ID</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">from_date</code> - Filter events from this date (YYYY-MM-DD)</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">to_date</code> - Filter events to this date (YYYY-MM-DD)</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">limit</code> - Number of events to return (1-1000, default: 50)</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">offset</code> - Number of events to skip (default: 0)</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">sort_by</code> - Sort field: created_at, updated_at, delivered_at</li>
                                    <li><code class="text-blue-600 dark:text-blue-400">sort_order</code> - Sort order: asc, desc (default: desc)</li>
                                </ul>
                            </div>
                            
                            <h5 class="font-semibold mb-2">Example Request:</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm mb-4"><code>GET /api/v1/events?status=failed&limit=10&from_date=2025-01-01</code></pre>
                            
                            <h5 class="font-semibold mb-2">Response (200 OK):</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm"><code>{
  "success": true,
  "data": {
    "events": [
      {
        "id": 1,
        "event_id": "evt_123456",
        "event_type": "user.created",
        "status": "delivered",
        "webhook_endpoint": {
          "id": 1,
          "name": "My Webhook",
          "slug": "my-webhook"
        },
        "payload": {...},
        "headers": {...},
        "source_ip": "192.168.1.1",
        "user_agent": "MyApp/1.0",
        "delivery_attempts": 1,
        "last_attempt_at": "2025-01-01T00:00:00.000000Z",
        "delivered_at": "2025-01-01T00:00:01.000000Z",
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-01-01T00:00:01.000000Z"
      }
    ],
    "pagination": {
      "total": 100,
      "limit": 10,
      "offset": 0,
      "has_more": true
    }
  }
}</code></pre>
                        </div>

                        <!-- Replay Single Event -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 rounded-full text-sm font-medium mr-3">
                                    POST
                                </span>
                                <h4 class="text-lg font-semibold">/events/{eventId}/replay</h4>
                            </div>
                            
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Replay a specific event by resetting its status and re-queuing it for delivery.
                            </p>
                            
                            <h5 class="font-semibold mb-2">Path Parameters:</h5>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                                <ul class="space-y-2 text-sm">
                                    <li><code class="text-blue-600 dark:text-blue-400">eventId</code> - The ID of the event to replay</li>
                                </ul>
                            </div>
                            
                            <h5 class="font-semibold mb-2">Example Request:</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm mb-4"><code>POST /api/v1/events/123/replay</code></pre>
                            
                            <h5 class="font-semibold mb-2">Response (200 OK):</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm"><code>{
  "success": true,
  "message": "Event queued for replay",
  "data": {
    "event_id": "evt_123456",
    "status": "pending"
  }
}</code></pre>
                        </div>

                        <!-- Replay Multiple Events -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 rounded-full text-sm font-medium mr-3">
                                    POST
                                </span>
                                <h4 class="text-lg font-semibold">/events/replay</h4>
                            </div>
                            
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Replay multiple events using filters or specific event IDs.
                            </p>
                            
                            <h5 class="font-semibold mb-2">Request Body:</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm mb-4"><code>{
  // Option 1: Filter-based replay
  "status": "failed", // optional: failed, delivered
  "event_type": "user.created", // optional
  "webhook_endpoint_id": 1, // optional
  "from_date": "2025-01-01", // optional
  "to_date": "2025-01-31", // optional
  "limit": 50, // optional, max 100
  
  // Option 2: Specific event IDs (takes precedence over filters)
  "event_ids": [1, 2, 3, 4, 5] // optional, max 100 IDs
}</code></pre>
                            
                            <h5 class="font-semibold mb-2">Response (200 OK):</h5>
                            <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm"><code>{
  "success": true,
  "message": "Replayed 5 events",
  "data": {
    "replayed_count": 5,
    "total_events": 5,
    "errors": [] // Any errors encountered during replay
  }
}</code></pre>
                        </div>

                    </div>

                    <!-- Error Responses -->
                    <div class="mt-8 border border-red-200 dark:border-red-800 rounded-lg p-6">
                        <h4 class="text-lg font-semibold mb-4 text-red-800 dark:text-red-200">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Error Responses
                        </h4>
                        
                        <div class="space-y-4">
                            <div>
                                <h5 class="font-semibold mb-2">401 Unauthorized</h5>
                                <pre class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 p-3 rounded text-sm"><code>{"error": "Invalid API key"}</code></pre>
                            </div>
                            
                            <div>
                                <h5 class="font-semibold mb-2">422 Validation Error</h5>
                                <pre class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 p-3 rounded text-sm"><code>{
  "error": "Validation failed",
  "details": {
    "name": ["The name field is required."]
  }
}</code></pre>
                            </div>
                            
                            <div>
                                <h5 class="font-semibold mb-2">404 Not Found</h5>
                                <pre class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 p-3 rounded text-sm"><code>{"error": "Event not found"}</code></pre>
                            </div>
                            
                            <div>
                                <h5 class="font-semibold mb-2">500 Internal Server Error</h5>
                                <pre class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 p-3 rounded text-sm"><code>{"error": "Internal server error"}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Rate Limits -->
                    <div class="mt-8 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                        <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                            <i class="fas fa-clock mr-2"></i>Rate Limits
                        </h4>
                        <p class="text-yellow-700 dark:text-yellow-300 text-sm">
                            API requests are subject to rate limiting. Please implement appropriate retry logic with exponential backoff 
                            if you encounter rate limit errors (HTTP 429).
                        </p>
                    </div>

                    <!-- Code Examples -->
                    <div class="mt-8">
                        <h4 class="text-lg font-semibold mb-4">Code Examples</h4>
                        
                        <div class="space-y-6">
                            <!-- cURL Example -->
                            <div>
                                <h5 class="font-semibold mb-2">cURL</h5>
                                <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm"><code># Create a webhook endpoint
curl -X POST {{ url('/api/v1/webhooks/endpoints') }} \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "name": "My Webhook",
    "destination_urls": ["https://your-app.com/webhook"],
    "auth_method": "hmac",
    "auth_secret": "your-secret"
  }'

# Get failed events
curl -X GET "{{ url('/api/v1/events') }}?status=failed&limit=10" \
  -H "X-API-Key: your-api-key"

# Replay an event
curl -X POST {{ url('/api/v1/events/123/replay') }} \
  -H "X-API-Key: your-api-key"</code></pre>
                            </div>
                            
                            <!-- JavaScript Example -->
                            <div>
                                <h5 class="font-semibold mb-2">JavaScript (fetch)</h5>
                                <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm"><code>// Create a webhook endpoint
const response = await fetch('{{ url('/api/v1/webhooks/endpoints') }}', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'your-api-key'
  },
  body: JSON.stringify({
    name: 'My Webhook',
    destination_urls: ['https://your-app.com/webhook'],
    auth_method: 'hmac',
    auth_secret: 'your-secret'
  })
});

const data = await response.json();
console.log(data);</code></pre>
                            </div>
                            
                            <!-- PHP Example -->
                            <div>
                                <h5 class="font-semibold mb-2">PHP</h5>
                                <pre class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg overflow-x-auto text-sm"><code>&lt;?php
// Create a webhook endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '{{ url("/api/v1/webhooks/endpoints") }}');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: your-api-key'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' =&gt; 'My Webhook',
    'destination_urls' =&gt; ['https://your-app.com/webhook'],
    'auth_method' =&gt; 'hmac',
    'auth_secret' =&gt; 'your-secret'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

print_r($data);</code></pre>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>