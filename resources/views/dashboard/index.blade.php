<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
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

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-4 sm:p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-paper-plane text-blue-500 text-xl sm:text-2xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Events</dt>
                                    <dd class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">{{ $stats['total_events'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-4 sm:p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 text-xl sm:text-2xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Successful</dt>
                                    <dd class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">{{ $stats['successful_deliveries'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-4 sm:p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-times-circle text-red-500 text-xl sm:text-2xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Failed</dt>
                                    <dd class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">{{ $stats['failed_deliveries'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-4 sm:p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-folder text-purple-500 text-xl sm:text-2xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Projects</dt>
                                    <dd class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">{{ $projects->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <!-- Projects Overview -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900 dark:text-white">Projects Overview</h3>
                            <a href="{{ route('dashboard.projects') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs sm:text-sm">View all</a>
                        </div>
                        <div class="space-y-4">
                            @forelse($projects as $project)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $project->name }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $project->description }}</p>
                                        </div>
                                        <div class="ml-2 flex-shrink-0">
                                            <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ $project->webhook_events_count }} events</span>
                                        <span>{{ $project->webhook_endpoints_count }} endpoints</span>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('dashboard.projects.show', $project) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs">View details</a>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No projects yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Recent Events -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900 dark:text-white">Recent Events</h3>
                            <a href="{{ route('dashboard.events') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs sm:text-sm">View all</a>
                        </div>
                        <div class="space-y-3">
                            @forelse($recentEvents as $event)
                                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($event->status === 'delivered') bg-green-100 text-green-800
                                                @elseif($event->status === 'success') bg-green-100 text-green-800
                                                @elseif($event->status === 'failed') bg-red-100 text-red-800
                                                @elseif($event->status === 'pending') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                <span class="hidden sm:inline">{{ ucfirst($event->status) }}</span>
                                                <span class="sm:hidden">{{ substr(ucfirst($event->status), 0, 3) }}</span>
                                            </span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $event->event_type }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                                            {{ $event->project->name }} • {{ $event->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <a href="{{ route('dashboard.events.show', $event) }}" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex-shrink-0">
                                        <i class="fas fa-external-link-alt text-sm"></i>
                                    </a>
                                </div>
                            @empty
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No recent events</p>
                            @endforelse
                        </div>
                        
                        <!-- Pagination Controls -->
                        @if($recentEvents->hasPages())
                            <div class="mt-4 flex justify-center">
                                <div class="flex items-center space-x-2">
                                    @if($recentEvents->onFirstPage())
                                        <span class="px-3 py-1 text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </span>
                                    @else
                                        <a href="{{ $recentEvents->previousPageUrl() }}" class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    @endif
                                    
                                    <span class="px-3 py-1 text-sm text-gray-600 dark:text-gray-300">
                                        Page {{ $recentEvents->currentPage() }} of {{ $recentEvents->lastPage() }}
                                    </span>
                                    
                                    @if($recentEvents->hasMorePages())
                                        <a href="{{ $recentEvents->nextPageUrl() }}" class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    @else
                                        <span class="px-3 py-1 text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Events Chart -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mt-4 sm:mt-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Events Over Time</h3>
                    <div id="eventsChart" style="height: 300px; min-height: 250px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Copy to clipboard functionality
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // You could add a toast notification here
                console.log('Copied to clipboard');
            });
        }

        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/dashboard/events-chart?days=7')
                .then(response => response.json())
                .then(data => {
                    Highcharts.chart('eventsChart', {
                        chart: {
                            type: 'line',
                            backgroundColor: 'transparent'
                        },
                        title: {
                            text: null
                        },
                        xAxis: {
                            categories: data.categories,
                            gridLineWidth: 1,
                            gridLineColor: '#e5e7eb'
                        },
                        yAxis: {
                            title: {
                                text: 'Events'
                            },
                            gridLineColor: '#e5e7eb'
                        },
                        tooltip: {
                            shared: true,
                            crosshairs: true
                        },
                        legend: {
                            align: 'center',
                            verticalAlign: 'bottom',
                            layout: 'horizontal'
                        },
                        series: [{
                            name: 'Total Events',
                            data: data.total,
                            color: '#3b82f6',
                            marker: {
                                symbol: 'circle'
                            }
                        }, {
                            name: 'Successful',
                            data: data.successful,
                            color: '#10b981',
                            marker: {
                                symbol: 'circle'
                            }
                        }, {
                            name: 'Failed',
                            data: data.failed,
                            color: '#ef4444',
                            marker: {
                                symbol: 'circle'
                            }
                        }],
                        responsive: {
                            rules: [{
                                condition: {
                                    maxWidth: 500
                                },
                                chartOptions: {
                                    legend: {
                                        layout: 'horizontal',
                                        align: 'center',
                                        verticalAlign: 'bottom'
                                    }
                                }
                            }]
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading chart data:', error);
                });
        });
    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>