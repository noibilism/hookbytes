<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Projects') }}
        </h2>
    </x-slot>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'JetBrains Mono', monospace;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 space-y-4 sm:space-y-0">
                <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
                <a href="{{ route('dashboard.projects.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-center">
                    <i class="fas fa-plus mr-2"></i>Create Project
                </a>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Projects Grid -->
            @if($projects->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @foreach($projects as $project)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $project->name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $project->slug }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $project->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $project->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            @if($project->description)
                                <p class="text-gray-600 text-sm mb-4">{{ $project->description }}</p>
                            @endif
                            
                            <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                                <span><i class="fas fa-link mr-1"></i>{{ $project->webhook_endpoints_count }} endpoints</span>
                                <span><i class="fas fa-paper-plane mr-1"></i>{{ $project->events_count }} events</span>
                            </div>
                            
                            <!-- Mini Chart -->
                            <div class="mb-4">
                                <div class="text-xs text-gray-500 mb-1">Today's Events</div>
                                <div id="projectChart{{ $project->id }}" style="height: 60px;"></div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="{{ route('dashboard.projects.show', $project) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-3 rounded text-sm font-medium">
                                    View Details
                                </a>
                                <a href="{{ route('dashboard.projects.show', $project) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded text-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-folder-open text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No projects yet</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first project to manage webhook endpoints.</p>
                    <a href="{{ route('dashboard.projects.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                        <i class="fas fa-plus mr-2"></i>Create Your First Project
                    </a>
                </div>
            @endif
        </div>
    </div>

    @foreach($projects as $project)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/dashboard/projects/{{ $project->id }}/events-chart', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                Highcharts.chart('projectChart{{ $project->id }}', {
                    chart: {
                        type: 'column',
                        height: 60,
                        backgroundColor: 'transparent',
                        margin: [0, 0, 0, 0],
                        spacing: [0, 0, 0, 0]
                    },
                    title: {
                        text: null
                    },
                    xAxis: {
                        categories: data.categories,
                        visible: false
                    },
                    yAxis: {
                        visible: false,
                        stackLabels: {
                            enabled: false
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            borderWidth: 0,
                            pointPadding: 0.1,
                            groupPadding: 0.05
                        }
                    },
                    tooltip: {
                        formatter: function() {
                            return '<b>' + this.x + '</b><br/>' +
                                   this.series.name + ': ' + this.y + '<br/>' +
                                   'Total: ' + this.point.stackTotal;
                        }
                    },
                    series: [{
                        name: 'Successful',
                        data: data.successful,
                        color: '#10B981'
                    }, {
                        name: 'Failed',
                        data: data.failed,
                        color: '#EF4444'
                    }],
                    credits: {
                        enabled: false
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data for project {{ $project->id }}:', error);
                document.getElementById('projectChart{{ $project->id }}').innerHTML = '<div class="text-xs text-gray-400 text-center py-4">No data</div>';
            });
        });
    </script>
    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>