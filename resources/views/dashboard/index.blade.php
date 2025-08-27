<x-dashboard-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Dashboard Overview
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Monitor your webhook subscriptions, events, and delivery performance.
                </p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 015.25 2h9.5A2.25 2.25 0 0117 4.25v8.5A2.25 2.25 0 0114.75 15h-9.5A2.25 2.25 0 013 12.75v-8.5zM6.25 6.5a.75.75 0 00-.75.75v3.5c0 .414.336.75.75.75h7.5a.75.75 0 00.75-.75v-3.5a.75.75 0 00-.75-.75h-7.5z" clip-rule="evenodd" />
                    </svg>
                    Export Report
                </button>
                <button type="button" class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    New Subscription
                </button>
            </div>
        </div>

        <!-- Stats Overview -->
        @livewire('dashboard.stats-overview')

        <!-- Charts and Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Delivery Success Rate Chart -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Delivery Success Rate</h3>
                    @livewire('dashboard.success-rate-chart')
                </div>
            </div>

            <!-- Recent Events -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Events</h3>
                    @livewire('dashboard.recent-events')
                </div>
            </div>
        </div>

        <!-- Failed Deliveries Alert -->
        @livewire('dashboard.failed-deliveries-alert')

        <!-- Active Subscriptions Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Active Subscriptions</h3>
            </div>
            @livewire('dashboard.active-subscriptions-table')
        </div>
    </div>
</x-dashboard-layout>