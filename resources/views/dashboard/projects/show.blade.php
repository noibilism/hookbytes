<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} - HookBytes Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'JetBrains Mono', 'SF Mono', Monaco, Inconsolata, 'Roboto Mono', Consolas, 'Courier New', monospace;
        }
        .font-mono {
            font-family: 'JetBrains Mono', 'SF Mono', Monaco, Inconsolata, 'Roboto Mono', Consolas, 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-800">HookBytes</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-gray-100' : '' }}">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                        <a href="{{ route('dashboard.events') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard.events') ? 'bg-gray-100' : '' }}">
                            <i class="fas fa-bolt mr-1"></i> Events
                        </a>
                        <a href="{{ route('dashboard.projects') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard.projects*') ? 'bg-gray-100' : '' }}">
                            <i class="fas fa-folder mr-1"></i> Projects
                        </a>
                        <a href="{{ route('dashboard.settings') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard.settings*') ? 'bg-gray-100' : '' }}">
                            <i class="fas fa-cog mr-1"></i> Settings
                        </a>
                        <a href="#" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-1"></i> API Docs
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center mb-2">
                    <a href="{{ route('dashboard.projects') }}" class="text-gray-500 hover:text-gray-700 mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $project->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $project->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if($project->description)
                    <p class="text-gray-600">{{ $project->description }}</p>
                @endif
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Project Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-link text-blue-500 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Webhook Endpoints</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $project->webhookEndpoints->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-paper-plane text-green-500 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Events</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $project->events->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-calendar text-purple-500 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Created</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $project->created_at->format('M j, Y') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Configuration Section -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">API Configuration</h2>
                    <p class="text-sm text-gray-500 mt-1">Use these credentials to authenticate API requests</p>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- API Key -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-key text-blue-500 mr-2"></i>API Key
                            </label>
                            <div class="flex items-center space-x-3">
                                <div class="flex-1">
                                    <input type="text" 
                                           id="api-key" 
                                           value="{{ $project->api_key }}" 
                                           readonly 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <button onclick="copyToClipboard('api-key')" 
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <i class="fas fa-copy"></i>
                                    <span>Copy</span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Include this in the X-API-Key header: <code class="bg-gray-100 px-1 rounded">X-API-Key: {{ $project->api_key }}</code></p>
                        </div>

                        <!-- Webhook Secret -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-shield-alt text-green-500 mr-2"></i>Webhook Secret
                            </label>
                            <div class="flex items-center space-x-3">
                                <div class="flex-1">
                                    <input type="password" 
                                           id="webhook-secret" 
                                           value="{{ $project->webhook_secret }}" 
                                           readonly 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <button onclick="toggleSecretVisibility('webhook-secret')" 
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <i class="fas fa-eye" id="webhook-secret-eye"></i>
                                    <span id="webhook-secret-text">Show</span>
                                </button>
                                <button onclick="copyToClipboard('webhook-secret')" 
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <i class="fas fa-copy"></i>
                                    <span>Copy</span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Use this secret to verify webhook signatures and secure your endpoints</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Chart Section -->
            <div class="bg-white shadow rounded-lg mb-6 sm:mb-8">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base sm:text-lg font-medium text-gray-900">Today's Events Activity</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Hourly breakdown of webhook events for today</p>
                </div>
                <div class="p-4 sm:p-6">
                    <div id="projectEventsChart" style="height: 250px; min-height: 200px;"></div>
                </div>
            </div>

            <!-- Webhook Endpoints Section -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Webhook Endpoints</h2>
                        <a href="{{ route('dashboard.endpoints.create', $project) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Create Endpoint
                        </a>
                    </div>
                </div>

                @if($project->webhookEndpoints->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Webhook URL</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auth Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($project->webhookEndpoints as $endpoint)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $endpoint->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $endpoint->slug }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <code class="bg-gray-100 px-2 py-1 rounded text-sm text-gray-800 mr-2">{{ url('/api/webhook/' . $endpoint->url_path) }}</code>
                                                <button onclick="copyToClipboard('{{ url('/api/webhook/' . $endpoint->url_path) }}')" class="text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $endpoint->auth_method === 'hmac' ? 'bg-green-100 text-green-800' : 
                                                   ($endpoint->auth_method === 'shared_secret' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst(str_replace('_', ' ', $endpoint->auth_method)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $endpoint->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $endpoint->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('dashboard.endpoints.edit', $endpoint) }}" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('dashboard.endpoints.destroy', $endpoint) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this endpoint?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-link text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No webhook endpoints yet</h3>
                        <p class="text-gray-500 mb-6">Create your first webhook endpoint to start receiving data from external services.</p>
                        <a href="{{ route('dashboard.endpoints.create', $project) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                            <i class="fas fa-plus mr-2"></i>Create Webhook Endpoint
                        </a>
                    </div>
                @endif
            </div>

            <!-- Recent Events -->
            @if($project->events->count() > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Recent Events</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($project->events as $event)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('dashboard.events.show', $event) }}" class="text-blue-600 hover:text-blue-900 font-mono text-sm">
                                                {{ $event->event_id }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $event->event_type }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $event->status === 'success' ? 'bg-green-100 text-green-800' : 
                                                   ($event->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ ucfirst($event->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $event->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // You could add a toast notification here
                console.log('Copied to clipboard: ' + text);
            });
        }

        // Initialize events chart
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/dashboard/projects/{{ $project->id }}/events-chart', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                Highcharts.chart('projectEventsChart', {
                    chart: {
                        type: 'column',
                        backgroundColor: 'transparent'
                    },
                    title: {
                        text: null
                    },
                    xAxis: {
                        categories: data.categories,
                        title: {
                            text: 'Hour of Day',
                            style: {
                                fontFamily: 'Inter, sans-serif',
                                fontSize: '12px',
                                color: '#6B7280'
                            }
                        },
                        labels: {
                            style: {
                                fontFamily: 'Inter, sans-serif',
                                fontSize: '11px',
                                color: '#6B7280'
                            }
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Number of Events',
                            style: {
                                fontFamily: 'Inter, sans-serif',
                                fontSize: '12px',
                                color: '#6B7280'
                            }
                        },
                        labels: {
                            style: {
                                fontFamily: 'Inter, sans-serif',
                                fontSize: '11px',
                                color: '#6B7280'
                            }
                        },
                        stackLabels: {
                            enabled: true,
                            style: {
                                fontFamily: 'Inter, sans-serif',
                                fontSize: '10px',
                                color: '#6B7280'
                            }
                        }
                    },
                    legend: {
                        enabled: true,
                        itemStyle: {
                            fontFamily: 'Inter, sans-serif',
                            fontSize: '12px',
                            color: '#374151'
                        }
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            borderWidth: 0,
                            pointPadding: 0.1,
                            groupPadding: 0.1
                        }
                    },
                    tooltip: {
                        style: {
                            fontFamily: 'Inter, sans-serif',
                            fontSize: '12px'
                        },
                        formatter: function() {
                            return '<b>' + this.x + '</b><br/>' +
                                   this.series.name + ': ' + this.y + '<br/>' +
                                   'Total: ' + this.point.stackTotal;
                        }
                    },
                    series: [{
                        name: 'Successful Events',
                        data: data.successful,
                        color: '#10B981'
                    }, {
                        name: 'Failed Events',
                        data: data.failed,
                        color: '#EF4444'
                    }],
                    credits: {
                        enabled: false
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
                document.getElementById('projectEventsChart').innerHTML = '<div class="text-center py-12 text-gray-500"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><br/>Unable to load chart data</div>';
            });
        });

        // Copy to clipboard function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const textToCopy = element.value;
            
            // Create a temporary textarea to copy the text
            const tempTextarea = document.createElement('textarea');
            tempTextarea.value = textToCopy;
            document.body.appendChild(tempTextarea);
            tempTextarea.select();
            document.execCommand('copy');
            document.body.removeChild(tempTextarea);
            
            // Show feedback
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i><span>Copied!</span>';
            button.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-green-500');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('bg-green-500');
                if (elementId === 'api-key') {
                    button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                } else {
                    button.classList.add('bg-green-600', 'hover:bg-green-700');
                }
            }, 2000);
        }

        // Toggle secret visibility
        function toggleSecretVisibility(elementId) {
            const element = document.getElementById(elementId);
            const eyeIcon = document.getElementById(elementId + '-eye');
            const buttonText = document.getElementById(elementId + '-text');
            
            if (element.type === 'password') {
                element.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                buttonText.textContent = 'Hide';
            } else {
                element.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                buttonText.textContent = 'Show';
            }
        }
    </script>
</body>
</html>