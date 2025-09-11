@extends('layouts.master')

@section('title', 'Event Details - HookBytes Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css">
@endpush

@section('content')
    <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Event Details</h1>
                        <p class="mt-1 text-sm text-gray-600">{{ $event->event_id }}</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="replayEvent('{{ $event->id }}')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-redo mr-2"></i>Replay Event
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Event Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Event Information</h3>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Event ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $event->event_id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Event Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $event->event_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Project</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $event->project->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Webhook Endpoint</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $event->webhookEndpoint->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($event->status === 'delivered') bg-green-100 text-green-800
                                            @elseif($event->status === 'success') bg-green-100 text-green-800
                                            @elseif($event->status === 'failed') bg-red-100 text-red-800
                                            @elseif($event->status === 'permanently_failed') bg-red-200 text-red-900
                                            @elseif($event->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($event->status === 'processing') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst(str_replace('_', ' ', $event->status)) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $event->created_at->format('M j, Y H:i:s T') }}</dd>
                                </div>
                                @if($event->failed_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Failed At</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $event->failed_at->format('M j, Y H:i:s T') }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Payload -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Event Payload</h3>
                                <button onclick="copyPayload()" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                            </div>
                            <div class="bg-gray-50 rounded-md p-4 overflow-x-auto">
                                <pre><code id="payload" class="language-json">{{ json_encode($event->payload, JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Deliveries -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Delivery Attempts</h3>
                            @if($event->deliveries->count() > 0)
                                <div class="space-y-4">
                                    @foreach($event->deliveries as $delivery)
                                        <div class="border rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        @if($delivery->status === 'success') bg-green-100 text-green-800
                                                        @elseif($delivery->status === 'failed') bg-red-100 text-red-800
                                                        @else bg-gray-100 text-gray-800 @endif">
                                                        {{ ucfirst($delivery->status) }}
                                                    </span>
                                                    <span class="text-sm font-medium text-gray-900">{{ $delivery->destination_url }}</span>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $delivery->created_at->format('M j, H:i:s') }}
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                                <div>
                                                    <span class="font-medium text-gray-500">HTTP Status:</span>
                                                    <span class="ml-1 @if($delivery->response_code >= 200 && $delivery->response_code < 300) text-green-600 @else text-red-600 @endif">
                                                        {{ $delivery->response_code ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="font-medium text-gray-500">Response Time:</span>
                                                    <span class="ml-1 text-gray-900">{{ $delivery->latency_ms ?? 'N/A' }}ms</span>
                                                </div>
                                                <div>
                                                    <span class="font-medium text-gray-500">Attempt:</span>
                                                    <span class="ml-1 text-gray-900">#{{ $loop->iteration }}</span>
                                                </div>
                                            </div>

                                            @if($delivery->error_message)
                                                <div class="mt-3 p-3 bg-red-50 rounded-md">
                                                    <div class="text-sm font-medium text-red-800">Error Message:</div>
                                                    <div class="text-sm text-red-700 mt-1 font-mono">{{ $delivery->error_message }}</div>
                                                </div>
                                            @endif

                                            @if($delivery->response_body)
                                                <div class="mt-3">
                                                    <button onclick="toggleResponse('{{ $delivery->id }}')" class="text-blue-600 hover:text-blue-800 text-sm">
                                                        <i class="fas fa-chevron-down mr-1"></i>Show Response
                                                    </button>
                                                    <div id="response-{{ $delivery->id }}" class="hidden mt-2 p-3 bg-gray-50 rounded-md">
                                                        <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ $delivery->response_body }}</pre>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4">No delivery attempts yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <button onclick="replayEvent('{{ $event->id }}')" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    <i class="fas fa-redo mr-2"></i>Replay Event
                                </button>
                                <button onclick="copyEventId()" class="w-full bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                                    <i class="fas fa-copy mr-2"></i>Copy Event ID
                                </button>
                                <a href="{{ route('dashboard.events') }}" class="w-full bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-center block">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to Events
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Event Timeline -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Timeline</h3>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">Event Created</div>
                                        <div class="text-gray-500">{{ $event->created_at->format('M j, H:i:s') }}</div>
                                    </div>
                                </div>
                                
                                @foreach($event->deliveries as $delivery)
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 @if($delivery->status === 'success') bg-green-500 @else bg-red-500 @endif rounded-full"></div>
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-900">Delivery Attempt #{{ $loop->iteration }}</div>
                                            <div class="text-gray-500">{{ $delivery->created_at->format('M j, H:i:s') }}</div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($event->failed_at)
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-red-600 rounded-full"></div>
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-900">Event Failed</div>
                                            <div class="text-gray-500">{{ $event->failed_at->format('M j, H:i:s') }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/autoloader/prism-autoloader.min.js"></script>
    <script>
        function copyPayload() {
            const payload = document.getElementById('payload').textContent;
            navigator.clipboard.writeText(payload).then(() => {
                alert('Payload copied to clipboard!');
            });
        }

        function copyEventId() {
            const eventId = '{{ $event->event_id }}';
            navigator.clipboard.writeText(eventId).then(() => {
                alert('Event ID copied to clipboard!');
            });
        }

        function toggleResponse(deliveryId) {
            const element = document.getElementById('response-' + deliveryId);
            element.classList.toggle('hidden');
        }

        function replayEvent(eventId) {
            if (!confirm('Are you sure you want to replay this event?')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log('CSRF Token:', csrfToken);

            fetch(`/dashboard/events/${eventId}/replay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    return response.text().then(text => {
                        console.log('Error response body:', text);
                        throw new Error(`HTTP error! status: ${response.status}, body: ${text.substring(0, 200)}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Success response:', data);
                if (data.success) {
                    alert('Event replayed successfully! New event ID: ' + data.new_event_id);
                } else {
                    alert('Failed to replay event: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Replay error:', error);
                alert('Error replaying event: ' + error.message);
            });
        }
    </script>
@endpush