<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Webhook Endpoint') }}
        </h2>
    </x-slot>

    <script src="https://cdn.tailwindcss.com"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center mb-2">
                    <a href="{{ route('dashboard.projects.show', $project) }}" class="text-gray-500 hover:text-gray-700 mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Create Webhook Endpoint</h1>
                </div>
                <p class="text-gray-600">Create a new webhook endpoint for {{ $project->name }}</p>
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

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('created_endpoint'))
                <!-- Created Endpoint Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <h3 class="text-lg font-medium text-blue-800">Endpoint Created Successfully!</h3>
                    </div>
                    <div class="text-sm text-blue-700">
                        <p><strong>Name:</strong> {{ session('created_endpoint')->name }}</p>
                        <p><strong>Webhook URL:</strong></p>
                        <code class="bg-white px-2 py-1 rounded text-xs">{{ url('/api/webhook/' . session('created_endpoint')->url_path) }}</code>
                    </div>
                </div>
            @endif

            <!-- Form -->
            <div class="bg-white shadow rounded-lg">
                <form action="{{ route('dashboard.endpoints.store', $project) }}" method="POST" class="p-6" onsubmit="prepareDestinationUrls()">
                    @csrf

                    <!-- Basic Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Endpoint Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., Payment Notifications">
                                <p class="text-sm text-gray-500 mt-1">A descriptive name for this webhook endpoint</p>
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">URL Slug</label>
                                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., payment-notifications">
                                <p class="text-sm text-gray-500 mt-1">Used in the webhook URL (letters, numbers, hyphens only)</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="destination_urls_text" class="block text-sm font-medium text-gray-700 mb-2">Destination URLs</label>
                            <textarea id="destination_urls_text" rows="3" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="https://your-app.com/webhook&#10;https://backup-app.com/webhook">{{ is_array(old('destination_urls')) ? implode("\n", old('destination_urls')) : old('destination_urls') }}</textarea>
                            <p class="text-sm text-gray-500 mt-1">Enter one URL per line. Webhook data will be forwarded to these URLs.</p>
                            <div id="destination_urls_array"></div>
                        </div>

                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Describe what this webhook endpoint will receive...">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <!-- Authentication -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Authentication</h3>
                        
                        <div class="mb-4">
                            <label for="auth_method" class="block text-sm font-medium text-gray-700 mb-2">Authentication Method</label>
                            <select id="auth_method" name="auth_method" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="none" {{ old('auth_method') === 'none' ? 'selected' : '' }}>None (Not recommended for production)</option>
                                <option value="shared_secret" {{ old('auth_method') === 'shared_secret' ? 'selected' : '' }}>Shared Secret</option>
                                <option value="hmac" {{ old('auth_method') === 'hmac' ? 'selected' : '' }}>HMAC Signature</option>
                            </select>
                        </div>

                        <div id="auth_config" class="hidden">
                            <div id="shared_secret_config" class="hidden">
                                <label for="shared_secret" class="block text-sm font-medium text-gray-700 mb-2">Shared Secret</label>
                                <div class="flex">
                                    <input type="text" id="shared_secret" name="shared_secret" value="{{ old('shared_secret') }}"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter a secure shared secret">
                                    <button type="button" onclick="generateSecret('shared_secret')"
                                        class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 text-sm">
                                        Generate
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">This secret will be sent in the X-Webhook-Secret header</p>
                            </div>

                            <div id="hmac_config" class="hidden">
                                <label for="hmac_secret" class="block text-sm font-medium text-gray-700 mb-2">HMAC Secret</label>
                                <div class="flex">
                                    <input type="text" id="hmac_secret" name="hmac_secret" value="{{ old('hmac_secret') }}"
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
                                <input type="number" id="max_retries" name="max_retries" value="{{ old('max_retries', 3) }}" min="0" max="10"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-sm text-gray-500 mt-1">Number of retry attempts for failed deliveries</p>
                            </div>

                            <div>
                                <label for="timeout_seconds" class="block text-sm font-medium text-gray-700 mb-2">Timeout (seconds)</label>
                                <input type="number" id="timeout_seconds" name="timeout_seconds" value="{{ old('timeout_seconds', 30) }}" min="5" max="300"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-sm text-gray-500 mt-1">Request timeout for webhook deliveries</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active (endpoint will receive webhook events)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- URL Preview -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Webhook URL Preview</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Your webhook URL will be:</p>
                            <code id="url_preview" class="bg-white px-3 py-2 rounded border text-sm text-gray-800">{{ url('/api/webhook/') }}/[slug]</code>
                            <p class="text-sm text-gray-500 mt-2">External services will send webhook data to this URL</p>
                        </div>
                    </div>

                    <!-- Webhook Testing -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Test Webhook (Optional)</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-4">After creating the webhook, you can test it with sample data to ensure it's working correctly.</p>
                            
                            <div class="mb-4">
                                <label for="test_payload" class="block text-sm font-medium text-gray-700 mb-2">Test Payload (JSON)</label>
                                <textarea id="test_payload" rows="6"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                                    placeholder='{\n  "event": "test",\n  "data": {\n    "message": "Hello from webhook test!"\n  }\n}'>{
  "event": "test",
  "data": {
    "message": "Hello from webhook test!"
  }
}</textarea>
                            </div>
                            
                            <button type="button" id="test_webhook_btn" onclick="testWebhook()" disabled
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                                Test Webhook
                            </button>
                            
                            <div id="test_result" class="mt-4 hidden">
                                <div class="p-3 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <span id="test_status" class="font-medium"></span>
                                        <span id="test_response_time" class="ml-2 text-sm text-gray-500"></span>
                                    </div>
                                    <pre id="test_response" class="text-sm bg-gray-100 p-2 rounded overflow-x-auto"></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('dashboard.projects.show', $project) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            Create Webhook Endpoint
                        </button>
                    </div>
                </form>
            </div>

            @if (session('created_endpoint'))
                <!-- Try Webhook Section -->
                <div class="bg-white shadow rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Try Your New Webhook</h3>
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-3">Test your newly created webhook endpoint by sending a sample payload:</p>
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
                </div>
            @endif
        </div>
    </div>

    <script>
        let currentWebhookUrl = '';
        
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value.trim();
            const slugField = document.getElementById('slug');
            
            // Only auto-generate if slug field is empty or contains only whitespace
            if (!slugField.value.trim()) {
                let slug = name
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                    .replace(/\s+/g, '-') // Replace spaces with hyphens
                    .replace(/-+/g, '-') // Replace multiple hyphens with single
                    .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
                
                // Add random suffix to make it unique
                if (slug) {
                    const randomSuffix = Math.random().toString(36).substring(2, 8);
                    slug = slug + '-' + randomSuffix;
                }
                
                slugField.value = slug;
                
                // Trigger slug change event to update URL preview
                slugField.dispatchEvent(new Event('input'));
            }
        });
        
        // Also trigger slug generation on page load if name has value but slug is empty
        document.addEventListener('DOMContentLoaded', function() {
            const nameField = document.getElementById('name');
            const slugField = document.getElementById('slug');
            
            if (nameField.value.trim() && !slugField.value.trim()) {
                nameField.dispatchEvent(new Event('input'));
            }
        });
        
        // Update URL preview when slug changes
        document.getElementById('slug').addEventListener('input', function() {
            const slug = this.value;
            const preview = document.getElementById('url_preview');
            const testBtn = document.getElementById('test_webhook_btn');
            
            if (slug) {
                currentWebhookUrl = '{{ url("/api/webhook/") }}/' + slug;
                preview.textContent = currentWebhookUrl;
                testBtn.disabled = false;
            } else {
                currentWebhookUrl = '';
                preview.textContent = '{{ url("/api/webhook/") }}/[slug]';
                testBtn.disabled = true;
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
        
        // Convert destination URLs textarea to array format for form submission
        function prepareDestinationUrls() {
            const textarea = document.getElementById('destination_urls_text');
            const arrayContainer = document.getElementById('destination_urls_array');
            
            // Clear existing hidden inputs
            arrayContainer.innerHTML = '';
            
            // Get URLs from textarea, split by newlines, and filter out empty lines
            const urls = textarea.value
                .split('\n')
                .map(url => url.trim())
                .filter(url => url.length > 0);
            
            // Create hidden input for each URL
            urls.forEach((url, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `destination_urls[${index}]`;
                input.value = url;
                arrayContainer.appendChild(input);
            });
        }
        
        // Test webhook functionality
        async function testWebhook() {
            if (!currentWebhookUrl) {
                alert('Please enter a webhook slug first to generate the URL.');
                return;
            }
            
            const testBtn = document.getElementById('test_webhook_btn');
            const testResult = document.getElementById('test_result');
            const testStatus = document.getElementById('test_status');
            const testResponseTime = document.getElementById('test_response_time');
            const testResponse = document.getElementById('test_response');
            
            // Get test payload
            let payload;
            try {
                payload = JSON.parse(document.getElementById('test_payload').value);
            } catch (e) {
                alert('Invalid JSON in test payload. Please check your syntax.');
                return;
            }
            
            // Disable button and show loading
            testBtn.disabled = true;
            testBtn.textContent = 'Testing...';
            testResult.classList.remove('hidden');
            testStatus.textContent = 'Sending test request...';
            testStatus.className = 'font-medium text-blue-600';
            testResponse.textContent = '';
            testResponseTime.textContent = '';
            
            const startTime = Date.now();
            
            try {
                // Get auth config for the test
                const authMethod = document.getElementById('auth_method').value;
                const headers = {
                    'Content-Type': 'application/json',
                    'X-Test-Request': 'true'
                };
                
                if (authMethod === 'shared_secret') {
                    const secret = document.getElementById('shared_secret').value;
                    if (secret) {
                        headers['X-Webhook-Secret'] = secret;
                    }
                } else if (authMethod === 'hmac') {
                    const secret = document.getElementById('hmac_secret').value;
                    if (secret) {
                        headers['X-Webhook-Signature'] = 'sha256=test-signature';
                    }
                }
                
                const response = await fetch(currentWebhookUrl, {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify(payload)
                });
                
                const responseTime = Date.now() - startTime;
                const responseText = await response.text();
                
                if (response.ok) {
                    testStatus.textContent = `✅ Test successful (${response.status})`;
                    testStatus.className = 'font-medium text-green-600';
                } else {
                    testStatus.textContent = `❌ Test failed (${response.status})`;
                    testStatus.className = 'font-medium text-red-600';
                }
                
                testResponseTime.textContent = `${responseTime}ms`;
                testResponse.textContent = responseText || 'No response body';
                
            } catch (error) {
                const responseTime = Date.now() - startTime;
                testStatus.textContent = '❌ Test failed (Network Error)';
                testStatus.className = 'font-medium text-red-600';
                testResponseTime.textContent = `${responseTime}ms`;
                testResponse.textContent = error.message;
            } finally {
                testBtn.disabled = false;
                testBtn.textContent = 'Test Webhook';
            }
        }

        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const slugField = document.getElementById('slug');
            if (!slugField.value) {
                const slug = name.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                slugField.value = slug;
                slugField.dispatchEvent(new Event('input'));
            }
        });
    </script>

    @if (session('created_endpoint'))
    <script>
        // Webhook testing functionality for created endpoint
        let isListening = false;
        let eventSource = null;
        const createdEndpointUrl = '{{ url("/api/webhook/" . session("created_endpoint")->url_path) }}';

        function sendTestWebhook() {
            const testPayload = {
                event: 'test',
                timestamp: new Date().toISOString(),
                data: {
                    message: 'This is a test webhook from HookBytes Dashboard',
                    endpoint_id: '{{ session("created_endpoint")->id }}',
                    test_id: Math.random().toString(36).substr(2, 9)
                }
            };

            fetch(createdEndpointUrl, {
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
    </script>
    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>