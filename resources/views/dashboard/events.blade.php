<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - HookBytes Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-lg" x-data="{ 
            open: false,
            darkMode: localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches),
            toggleDarkMode() {
                this.darkMode = !this.darkMode;
                localStorage.setItem('darkMode', this.darkMode);
                if (this.darkMode) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        }" x-init="
            if (darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        ">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-800 dark:text-white">HookBytes</a>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button @click="toggleDarkMode()" class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg x-show="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>
                        <a href="{{ route('dashboard') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                        <a href="{{ route('dashboard.events') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard.events') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                            <i class="fas fa-bolt mr-1"></i> Events
                        </a>
                        <a href="{{ route('dashboard.projects') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard.projects*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                            <i class="fas fa-folder mr-1"></i> Projects
                        </a>
                        <a href="{{ route('dashboard.settings') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard.settings*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                            <i class="fas fa-cog mr-1"></i> Settings
                        </a>
                        <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-1"></i> API Docs
                        </a>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button @click="open = !open" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:text-gray-900 dark:focus:text-white p-2">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile Navigation Menu -->
                <div x-show="open" x-transition class="md:hidden">
                    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                        <a href="{{ route('dashboard.events') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('dashboard.events') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-bolt mr-2"></i> Events
                        </a>
                        <a href="{{ route('dashboard.projects') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('dashboard.projects*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-folder mr-2"></i> Projects
                        </a>
                        <a href="{{ route('dashboard.settings') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('dashboard.settings*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-cog mr-2"></i> Settings
                        </a>
                        <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-book mr-2"></i> API Docs
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-6 sm:mb-8">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Webhook Events</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Monitor and manage your webhook event deliveries</p>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Project Filter -->
                        <div>
                            <label for="project_id" class="block text-sm font-medium text-gray-700">Project</label>
                            <select name="project_id" id="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Projects</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Endpoint Filter -->
                        <div>
                            <label for="endpoint_id" class="block text-sm font-medium text-gray-700">Endpoint</label>
                            <select name="endpoint_id" id="endpoint_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Endpoints</option>
                                @foreach($endpoints as $endpoint)
                                    <option value="{{ $endpoint->id }}" {{ request('endpoint_id') == $endpoint->id ? 'selected' : '' }}>
                                        {{ $endpoint->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="permanently_failed" {{ request('status') == 'permanently_failed' ? 'selected' : '' }}>Permanently Failed</option>
                            </select>
                        </div>

                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search Payload</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                   placeholder="Search in payload..." 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">From Date</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700">To Date</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type</label>
                            <input type="text" name="event_type" id="event_type" value="{{ request('event_type') }}" 
                                   placeholder="e.g. user.created" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Filter Actions -->
                        <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row items-stretch sm:items-end space-y-2 sm:space-y-0 sm:space-x-2 pt-4 sm:pt-0">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-center">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="{{ route('dashboard.events') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 text-center">
                                <i class="fas fa-times mr-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Events Chart -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900 mb-4">Events Overview</h3>
                    <div id="eventsChart" style="height: 200px; min-height: 150px;"></div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div id="bulkActions" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <span id="selectedCount" class="text-sm font-medium text-blue-900">0 events selected</span>
                        <button id="selectAllVisible" class="text-sm text-blue-600 hover:text-blue-800 underline">
                            Select all visible
                        </button>
                        <button id="clearSelection" class="text-sm text-blue-600 hover:text-blue-800 underline">
                            Clear selection
                        </button>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button id="bulkRetryBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-redo mr-2"></i>Retry Selected Events
                        </button>
                    </div>
                </div>
            </div>

            <!-- Events Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="overflow-x-auto -mx-4 sm:mx-0">
                        <div class="inline-block min-w-full py-2 align-middle">
                            <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Project</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Deliveries</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Created</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($events as $event)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="selected_events[]" value="{{ $event->id }}" class="event-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </td>
                                        <td class="px-3 sm:px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 truncate max-w-xs sm:max-w-none">{{ $event->event_type }}</div>
                                                <div class="text-xs sm:text-sm text-gray-500 truncate max-w-xs sm:max-w-none">{{ $event->event_id }}</div>
                                            </div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 hidden sm:table-cell">
                                            <div class="text-sm text-gray-900">{{ $event->project->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $event->webhookEndpoint->name }}</div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($event->status === 'delivered') bg-green-100 text-green-800
                                                @elseif($event->status === 'success') bg-green-100 text-green-800
                                                @elseif($event->status === 'failed') bg-red-100 text-red-800
                                                @elseif($event->status === 'permanently_failed') bg-red-200 text-red-900
                                                @elseif($event->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($event->status === 'processing') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                <span class="hidden sm:inline">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                                                <span class="sm:hidden">{{ substr(ucfirst($event->status), 0, 3) }}</span>
                                            </span>
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500 hidden md:table-cell">
                                            {{ $event->deliveries->count() }} deliveries
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500 hidden lg:table-cell">
                                            <span class="hidden sm:inline">{{ $event->created_at->format('M j, Y H:i') }}</span>
                                            <span class="sm:hidden">{{ $event->created_at->format('M j') }}</span>
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-1 sm:space-x-2">
                                                <a href="{{ route('dashboard.events.show', $event) }}" class="text-blue-600 hover:text-blue-900 p-1">
                                                    <i class="fas fa-eye text-sm"></i>
                                                </a>
                                                <button onclick="replayEvent('{{ $event->id }}')" class="text-green-600 hover:text-green-900 p-1">
                                                    <i class="fas fa-redo text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 sm:px-6 py-8 text-center text-gray-500">
                                            <div class="text-sm sm:text-base">No events found matching your criteria.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($events->hasPages())
                        <div class="mt-6">
                            {{ $events->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Replay Modal -->
    <div id="replayModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Replay Event</h3>
            <p class="text-sm text-gray-600 mb-4">This will create a new event with the same payload and dispatch it for processing.</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeReplayModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                    Cancel
                </button>
                <button id="confirmReplay" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Replay Event
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Retry Modal -->
    <div id="bulkRetryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Bulk Retry Events</h3>
            <p class="text-sm text-gray-600 mb-4">This will create new events for each selected event with the same payload and dispatch them for processing.</p>
            <p id="bulkRetryCount" class="text-sm font-medium text-blue-600 mb-4"></p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeBulkRetryModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                    Cancel
                </button>
                <button id="confirmBulkRetry" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-redo mr-2"></i>Retry Selected Events
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentEventId = null;

        function replayEvent(eventId) {
            currentEventId = eventId;
            document.getElementById('replayModal').classList.remove('hidden');
            document.getElementById('replayModal').classList.add('flex');
        }

        function closeReplayModal() {
            document.getElementById('replayModal').classList.add('hidden');
            document.getElementById('replayModal').classList.remove('flex');
            currentEventId = null;
        }

        document.getElementById('confirmReplay').addEventListener('click', function() {
            if (!currentEventId) return;

            fetch(`/dashboard/events/${currentEventId}/replay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Event replayed successfully!');
                    location.reload();
                } else {
                    alert('Failed to replay event: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error replaying event: ' + error.message);
            })
            .finally(() => {
                closeReplayModal();
            });
        });

        // Close modal when clicking outside
        document.getElementById('replayModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReplayModal();
            }
        });

        // Bulk retry functionality
        let selectedEvents = new Set();

        function updateBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            const bulkRetryBtn = document.getElementById('bulkRetryBtn');
            
            if (selectedEvents.size > 0) {
                bulkActions.classList.remove('hidden');
                selectedCount.textContent = `${selectedEvents.size} event${selectedEvents.size > 1 ? 's' : ''} selected`;
                bulkRetryBtn.disabled = false;
            } else {
                bulkActions.classList.add('hidden');
                bulkRetryBtn.disabled = true;
            }
        }

        function toggleEventSelection(eventId, checked) {
            if (checked) {
                selectedEvents.add(eventId);
            } else {
                selectedEvents.delete(eventId);
            }
            updateBulkActions();
            updateSelectAllCheckbox();
        }

        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const eventCheckboxes = document.querySelectorAll('.event-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.event-checkbox:checked');
            
            if (checkedCheckboxes.length === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (checkedCheckboxes.length === eventCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
            }
        }

        function showBulkRetryModal() {
            const count = selectedEvents.size;
            document.getElementById('bulkRetryCount').textContent = `You are about to retry ${count} event${count > 1 ? 's' : ''}.`;
            document.getElementById('bulkRetryModal').classList.remove('hidden');
            document.getElementById('bulkRetryModal').classList.add('flex');
        }

        function closeBulkRetryModal() {
            document.getElementById('bulkRetryModal').classList.add('hidden');
            document.getElementById('bulkRetryModal').classList.remove('flex');
        }

        // Event listeners
        document.getElementById('selectAll').addEventListener('change', function() {
            const eventCheckboxes = document.querySelectorAll('.event-checkbox');
            eventCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                toggleEventSelection(checkbox.value, this.checked);
            });
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('event-checkbox')) {
                toggleEventSelection(e.target.value, e.target.checked);
            }
        });

        document.getElementById('selectAllVisible').addEventListener('click', function() {
            const eventCheckboxes = document.querySelectorAll('.event-checkbox');
            eventCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
                toggleEventSelection(checkbox.value, true);
            });
        });

        document.getElementById('clearSelection').addEventListener('click', function() {
            const eventCheckboxes = document.querySelectorAll('.event-checkbox');
            eventCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectedEvents.clear();
            updateBulkActions();
            updateSelectAllCheckbox();
        });

        document.getElementById('bulkRetryBtn').addEventListener('click', function() {
            if (selectedEvents.size > 0) {
                showBulkRetryModal();
            }
        });

        document.getElementById('confirmBulkRetry').addEventListener('click', function() {
            if (selectedEvents.size === 0) return;

            const eventIds = Array.from(selectedEvents);
            
            fetch('/dashboard/events/bulk-retry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    event_ids: eventIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully retried ${data.retried_count} event${data.retried_count > 1 ? 's' : ''}!`);
                    location.reload();
                } else {
                    alert('Failed to retry events: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error retrying events: ' + error.message);
            })
            .finally(() => {
                closeBulkRetryModal();
            });
        });

        // Close bulk retry modal when clicking outside
        document.getElementById('bulkRetryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBulkRetryModal();
            }
        });

        // Initialize Events Chart
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/dashboard/events-chart?today=1')
                .then(response => response.json())
                .then(data => {
                    Highcharts.chart('eventsChart', {
                        chart: {
                            type: 'column',
                            stacking: 'normal',
                            height: 200
                        },
                        title: {
                            text: null
                        },
                        xAxis: {
                            categories: data.categories,
                            title: {
                                text: 'Hour'
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Number of Events'
                            },
                            stackLabels: {
                                enabled: true,
                                style: {
                                    fontWeight: 'bold',
                                    color: 'gray'
                                }
                            }
                        },
                        legend: {
                            align: 'right',
                            x: -30,
                            verticalAlign: 'top',
                            y: 25,
                            floating: true,
                            backgroundColor: 'white',
                            borderColor: '#CCC',
                            borderWidth: 1,
                            shadow: false
                        },
                        tooltip: {
                            headerFormat: '<b>{point.x}</b><br/>',
                            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
                        },
                        plotOptions: {
                            column: {
                                stacking: 'normal',
                                dataLabels: {
                                    enabled: false
                                }
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
                        responsive: {
                            rules: [{
                                condition: {
                                    maxWidth: 500
                                },
                                chartOptions: {
                                    legend: {
                                        floating: false,
                                        layout: 'horizontal',
                                        align: 'center',
                                        verticalAlign: 'bottom',
                                        x: 0,
                                        y: 0
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
</body>
</html>