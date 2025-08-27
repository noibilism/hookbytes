<x-dashboard-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    API Keys
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage your API keys for webhook authentication
                </p>
            </div>
        </div>

        <!-- API Key Manager Component -->
        <livewire:api-key-manager />

        <!-- Documentation Section -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">API Documentation</h3>
            
            <div class="prose prose-sm max-w-none">
                <h4 class="text-base font-medium text-gray-900">Authentication</h4>
                <p class="text-gray-600">
                    All API requests must be authenticated using your API key. You can provide the API key in one of three ways:
                </p>
                
                <div class="mt-4 space-y-4">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">1. Authorization Header (Recommended)</h5>
                        <div class="mt-1 bg-gray-50 rounded-md p-3">
                            <code class="text-sm font-mono text-gray-900">Authorization: Bearer wh_your_api_key_here</code>
                        </div>
                    </div>
                    
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">2. Custom Header</h5>
                        <div class="mt-1 bg-gray-50 rounded-md p-3">
                            <code class="text-sm font-mono text-gray-900">X-API-Key: wh_your_api_key_here</code>
                        </div>
                    </div>
                    
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">3. Query Parameter</h5>
                        <div class="mt-1 bg-gray-50 rounded-md p-3">
                            <code class="text-sm font-mono text-gray-900">GET /api/subscriptions?api_key=wh_your_api_key_here</code>
                        </div>
                    </div>
                </div>
                
                <h4 class="text-base font-medium text-gray-900 mt-6">Available Endpoints</h4>
                <div class="mt-2">
                    <div class="bg-gray-50 rounded-md p-4">
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">GET</span>
                                <code class="text-sm font-mono">/api/user</code>
                                <span class="text-sm text-gray-500">Get authenticated user info</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">POST</span>
                                <code class="text-sm font-mono">/api/subscriptions</code>
                                <span class="text-sm text-gray-500">Create webhook subscription</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">GET</span>
                                <code class="text-sm font-mono">/api/subscriptions</code>
                                <span class="text-sm text-gray-500">List webhook subscriptions</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">GET</span>
                                <code class="text-sm font-mono">/api/events</code>
                                <span class="text-sm text-gray-500">List webhook events</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">POST</span>
                                <code class="text-sm font-mono">/api/events</code>
                                <span class="text-sm text-gray-500">Create webhook event</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">GET</span>
                                <code class="text-sm font-mono">/api/deliveries</code>
                                <span class="text-sm text-gray-500">List delivery attempts</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">POST</span>
                                <code class="text-sm font-mono">/api/deliveries/replay</code>
                                <span class="text-sm text-gray-500">Replay failed deliveries</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">GET</span>
                                <code class="text-sm font-mono">/api/api-key</code>
                                <span class="text-sm text-gray-500">Get API key info</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">POST</span>
                                <code class="text-sm font-mono">/api/api-key/generate</code>
                                <span class="text-sm text-gray-500">Generate new API key</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">PUT</span>
                                <code class="text-sm font-mono">/api/api-key/regenerate</code>
                                <span class="text-sm text-gray-500">Regenerate API key</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">DELETE</span>
                                <code class="text-sm font-mono">/api/api-key</code>
                                <span class="text-sm text-gray-500">Revoke API key</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h4 class="text-base font-medium text-gray-900 mt-6">Security Best Practices</h4>
                <ul class="mt-2 text-sm text-gray-600 space-y-1">
                    <li>• Keep your API key secure and never share it publicly</li>
                    <li>• Use HTTPS for all API requests</li>
                    <li>• Regenerate your API key if you suspect it has been compromised</li>
                    <li>• Monitor the "Last Used" timestamp to detect unauthorized usage</li>
                    <li>• Use the Authorization header method when possible for better security</li>
                </ul>
            </div>
        </div>
    </div>
</x-dashboard-layout>