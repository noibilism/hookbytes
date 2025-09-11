@extends('layouts.master')

@section('title', 'Edit Webhook Endpoint - HookBytes Dashboard')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center mb-2">
                    <a href="{{ route('dashboard.projects.show', $endpoint->project) }}" class="text-gray-500 hover:text-gray-700 mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Webhook Endpoint</h1>
                </div>
                <p class="text-gray-600">Edit webhook endpoint for {{ $endpoint->project->name }}</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Current URL Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <div>
                        <p class="text-sm font-medium text-blue-800">Current Webhook URL:</p>
                        <code class="text-sm text-blue-700">{{ url('/api/webhook/' . $endpoint->url_path) }}</code>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white shadow rounded-lg">
                <form action="{{ route('dashboard.endpoints.update', $endpoint) }}" method="POST" class="p-6" onsubmit="prepareDestinationUrls()">
                    @csrf
                    @method('PATCH')

                    <!-- Basic Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Endpoint Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $endpoint->name) }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., Payment Notifications">
                                <p class="text-sm text-gray-500 mt-1">A descriptive name for this webhook endpoint</p>
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">URL Slug</label>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $endpoint->slug) }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., payment-notifications">
                                <p class="text-sm text-gray-500 mt-1">Used in the webhook URL (letters, numbers, hyphens only)</p>
                                <p class="text-sm text-orange-600 mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Changing this will update the webhook URL</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="destination_urls_text" class="block text-sm font-medium text-gray-700 mb-2">Destination URLs</label>
                            <textarea id="destination_urls_text" rows="3" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="https://your-app.com/webhook&#10;https://backup-app.com/webhook">{{ is_array(old('destination_urls', $endpoint->destination_urls)) ? implode("\n", old('destination_urls', $endpoint->destination_urls)) : old('destination_urls', implode("\n", $endpoint->destination_urls ?? [])) }}</textarea>
                            <p class="text-sm text-gray-500 mt-1">Enter one URL per line. Webhook data will be forwarded to these URLs.</p>
                            <div id="destination_urls_array"></div>
                        </div>

                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Describe what this webhook endpoint will receive...">{{ old('description', $endpoint->description) }}</textarea>
                        </div>
                    </div>

                    <!-- Authentication -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Authentication</h3>
                        
                        <div class="mb-4">
                            <label for="auth_method" class="block text-sm font-medium text-gray-700 mb-2">Authentication Method</label>
                            <select id="auth_method" name="auth_method" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="none" {{ old('auth_method', $endpoint->auth_method) === 'none' ? 'selected' : '' }}>None (Not recommended for production)</option>
                                <option value="shared_secret" {{ old('auth_method', $endpoint->auth_method) === 'shared_secret' ? 'selected' : '' }}>Shared Secret</option>
                                <option value="hmac" {{ old('auth_method', $endpoint->auth_method) === 'hmac' ? 'selected' : '' }}>HMAC Signature</option>
                            </select>
                        </div>

                        <div id="auth_config" class="{{ old('auth_method', $endpoint->auth_method) === 'none' ? 'hidden' : '' }}">
                            <div id="shared_secret_config" class="{{ old('auth_method', $endpoint->auth_method) !== 'shared_secret' ? 'hidden' : '' }}">
                                <label for="shared_secret" class="block text-sm font-medium text-gray-700 mb-2">Shared Secret</label>
                                <div class="flex">
                                    <input type="text" id="shared_secret" name="shared_secret" 
                                        value="{{ old('shared_secret', $endpoint->auth_method === 'shared_secret' ? $endpoint->auth_config['secret'] ?? '' : '') }}"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter a secure shared secret">
                                    <button type="button" onclick="generateSecret('shared_secret')"
                                        class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 text-sm">
                                        Generate
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">This secret will be sent in the X-Webhook-Secret header</p>
                            </div>

                            <div id="hmac_config" class="{{ old('auth_method', $endpoint->auth_method) !== 'hmac' ? 'hidden' : '' }}">
                                <label for="hmac_secret" class="block text-sm font-medium text-gray-700 mb-2">HMAC Secret</label>
                                <div class="flex">
                                    <input type="text" id="hmac_secret" name="hmac_secret" 
                                        value="{{ old('hmac_secret', $endpoint->auth_method === 'hmac' ? $endpoint->auth_config['secret'] ?? '' : '') }}"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter HMAC secret key">
                                    <button type="button" onclick="generateSecret('hmac_secret')"
                                        class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 text-sm">
                                        Generate
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Used to generate HMAC-SHA256 signature in X-Webhook-Signature header</p>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Configuration</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="max_retries" class="block text-sm font-medium text-gray-700 mb-2">Max Retries</label>
                                <input type="number" id="max_retries" name="max_retries" value="{{ old('max_retries', $endpoint->max_retries) }}" min="0" max="10"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-sm text-gray-500 mt-1">Number of retry attempts for failed deliveries</p>
                            </div>

                            <div>
                                <label for="timeout_seconds" class="block text-sm font-medium text-gray-700 mb-2">Timeout (seconds)</label>
                                <input type="number" id="timeout_seconds" name="timeout_seconds" value="{{ old('timeout_seconds', $endpoint->timeout_seconds) }}" min="5" max="300"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-sm text-gray-500 mt-1">Request timeout for webhook deliveries</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $endpoint->is_active) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active (endpoint will receive webhook events)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Headers Configuration -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Custom Headers</h3>
                        <div class="space-y-4">
                            @if($endpoint->headers_config && count($endpoint->headers_config) > 0)
                                @foreach($endpoint->headers_config as $key => $value)
                                    <div class="flex space-x-3">
                                        <div class="flex-1">
                                            <input type="text" name="header_keys[]" value="{{ $key }}" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="Header name (e.g., X-Custom-Header)">
                                        </div>
                                        <div class="flex-1">
                                            <input type="text" name="header_values[]" value="{{ $value }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="Header value">
                                        </div>
                                        <button type="button" onclick="removeHeaderRow(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @else
                                <div class="flex space-x-3">
                                    <div class="flex-1">
                                        <input type="text" name="header_keys[]" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Header name (e.g., X-Custom-Header)">
                                    </div>
                                    <div class="flex-1">
                                        <input type="text" name="header_values[]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Header value">
                                    </div>
                                    <button type="button" onclick="removeHeaderRow(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <button type="button" onclick="addHeaderRow()" class="mt-3 px-4 py-2 text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-plus mr-1"></i>Add Header
                        </button>
                        <p class="text-sm text-gray-500 mt-2">Custom headers to include with webhook deliveries</p>
                    </div>

                    <!-- URL Preview -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Webhook URL Preview</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Your webhook URL will be:</p>
                            <code id="url_preview" class="bg-white px-3 py-2 rounded border text-sm text-gray-800">{{ url('/api/webhook/' . $endpoint->url_path) }}</code>
                            <p class="text-sm text-gray-500 mt-2">External services will send webhook data to this URL</p>
                        </div>
                    </div>

                    <!-- Try Webhook -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Try Webhook</h3>
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-3">Test your webhook endpoint by sending a sample payload:</p>
                                <div class="flex space-x-3">
                                    <button type="button" onclick="sendTestWebhook()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                                        <i class="fas fa-paper-plane mr-2"></i>Send Test Webhook
                                    </button>
                                    <button type="button" onclick="toggleEventListener()" id="listen-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                                        <i class="fas fa-play mr-2"></i>Start Listening
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Event Log -->
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-700">Recent Events</h4>
                                    <button type="button" onclick="clearEventLog()" class="text-xs text-gray-500 hover:text-gray-700">
                                        Clear Log
                                    </button>
                                </div>
                                <div id="event-log" class="bg-white border rounded-lg p-3 h-32 overflow-y-auto text-xs font-mono">
                                    <div class="text-gray-500">No events yet. Click "Start Listening" to monitor incoming webhooks.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('dashboard.projects.show', $endpoint->project) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            Update Webhook Endpoint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Update URL preview when slug changes
        document.getElementById('slug').addEventListener('input', function() {
            const slug = this.value;
            const preview = document.getElementById('url_preview');
            if (slug) {
                preview.textContent = '{{ url("/api/webhook/") }}/' + slug;
            } else {
                preview.textContent = '{{ url("/api/webhook/") }}/[slug]';
            }
        });

        // Show/hide auth configuration based on method
        document.getElementById('auth_method').addEventListener('change', function() {
            const method = this.value;
            const authConfig = document.getElementById('auth_config');
            const sharedSecretConfig = document.getElementById('shared_secret_config');
            const hmacConfig = document.getElementById('hmac_config');

            if (method === 'none') {
                authConfig.classList.add('hidden');
            } else {
                authConfig.classList.remove('hidden');
                
                if (method === 'shared_secret') {
                    sharedSecretConfig.classList.remove('hidden');
                    hmacConfig.classList.add('hidden');
                } else if (method === 'hmac') {
                    sharedSecretConfig.classList.add('hidden');
                    hmacConfig.classList.remove('hidden');
                }
            }
        });

        // Generate random secret
        function generateSecret(fieldId) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            for (let i = 0; i < 32; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById(fieldId).value = result;
        }
        
        // Prepare destination URLs for form submission
        function prepareDestinationUrls() {
            const textarea = document.getElementById('destination_urls_text');
            const container = document.getElementById('destination_urls_array');
            
            // Clear existing hidden inputs
            container.innerHTML = '';
            
            // Split textarea content by newlines and create hidden inputs
            const urls = textarea.value.split('\n')
                .map(url => url.trim())
                .filter(url => url.length > 0);
            
            urls.forEach((url, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `destination_urls[${index}]`;
                input.value = url;
                container.appendChild(input);
            });
        }

        // Webhook testing functionality
        let isListening = false;
        let eventSource = null;

        function sendTestWebhook() {
            const webhookUrl = '{{ url("/api/webhook/" . $endpoint->url_path) }}';
            const testPayload = {
                event: 'test',
                timestamp: new Date().toISOString(),
                data: {
                    message: 'This is a test webhook from HookBytes Dashboard',
                    endpoint_id: '{{ $endpoint->id }}',
                    test_id: Math.random().toString(36).substr(2, 9)
                }
            };

            fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(testPayload)
            })
            .then(response => {
                if (response.ok) {
                    addEventToLog('✅ Test webhook sent successfully', 'success');
                } else {
                    addEventToLog('❌ Failed to send test webhook: ' + response.status, 'error');
                }
            })
            .catch(error => {
                addEventToLog('❌ Error sending test webhook: ' + error.message, 'error');
            });
        }

        function toggleEventListener() {
            const btn = document.getElementById('listen-btn');
            
            if (!isListening) {
                startListening();
                btn.innerHTML = '<i class="fas fa-stop mr-2"></i>Stop Listening';
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                btn.classList.add('bg-red-600', 'hover:bg-red-700');
                isListening = true;
            } else {
                stopListening();
                btn.innerHTML = '<i class="fas fa-play mr-2"></i>Start Listening';
                btn.classList.remove('bg-red-600', 'hover:bg-red-700');
                btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                isListening = false;
            }
        }

        function startListening() {
            addEventToLog('🎧 Started listening for webhook events...', 'info');
            
            // Simulate event listening (in a real implementation, this would connect to a WebSocket or SSE)
            // For now, we'll just show that the system is listening
        }

        function stopListening() {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            addEventToLog('⏹️ Stopped listening for events', 'info');
        }

        function addEventToLog(message, type = 'info') {
            const log = document.getElementById('event-log');
            const timestamp = new Date().toLocaleTimeString();
            
            const eventDiv = document.createElement('div');
            eventDiv.className = `mb-1 ${type === 'error' ? 'text-red-600' : type === 'success' ? 'text-green-600' : 'text-gray-700'}`;
            eventDiv.innerHTML = `<span class="text-gray-500">[${timestamp}]</span> ${message}`;
            
            // Remove placeholder text if it exists
            const placeholder = log.querySelector('.text-gray-500');
            if (placeholder && placeholder.textContent.includes('No events yet')) {
                placeholder.remove();
            }
            
            log.appendChild(eventDiv);
            log.scrollTop = log.scrollHeight;
        }

        function clearEventLog() {
            const log = document.getElementById('event-log');
            log.innerHTML = '<div class="text-gray-500">No events yet. Click "Start Listening" to monitor incoming webhooks.</div>';
        }

        function addHeaderRow() {
            const container = document.querySelector('.space-y-4');
            const newRow = document.createElement('div');
            newRow.className = 'flex space-x-3';
            newRow.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="header_keys[]" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Header name (e.g., X-Custom-Header)">
                </div>
                <div class="flex-1">
                    <input type="text" name="header_values[]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Header value">
                </div>
                <button type="button" onclick="removeHeaderRow(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
        }

        function removeHeaderRow(button) {
            const row = button.closest('.flex');
            const container = row.parentElement;
            if (container.children.length > 1) {
                row.remove();
            }
        }
    </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush