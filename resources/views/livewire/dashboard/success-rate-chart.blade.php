<div>
    <!-- Period Selector -->
    <div class="mb-4">
        <select wire:model.live="period" class="block w-32 rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
            <option value="24h">24 Hours</option>
            <option value="7d">7 Days</option>
            <option value="30d">30 Days</option>
        </select>
    </div>

    @if(count($chartData) > 0)
        <!-- Simple Bar Chart -->
        <div class="space-y-3">
            @foreach($chartData as $data)
                <div class="flex items-center space-x-3">
                    <div class="w-12 text-xs text-gray-500 text-right">{{ $data['date'] }}</div>
                    <div class="flex-1 bg-gray-200 rounded-full h-4 relative">
                         <div class="bg-green-500 h-4 rounded-full transition-all duration-300" 
                              style="width: {{ $data['success_rate'] }}%;"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-xs font-medium text-gray-700">
                            {{ $data['success_rate'] }}%
                        </div>
                    </div>
                    <div class="w-16 text-xs text-gray-500">
                        {{ $data['successful'] }}/{{ $data['total'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Legend -->
        <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
            <span>0%</span>
            <span>Success Rate</span>
            <span>100%</span>
        </div>
    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No delivery data</h3>
            <p class="mt-1 text-sm text-gray-500">Start sending webhooks to see success rate trends.</p>
        </div>
    @endif
</div>
