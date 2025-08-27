<div>
    @if(count($events) > 0)
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                @foreach($events as $index => $event)
                    <li>
                        <div class="relative pb-8">
                            @if($index < count($events) - 1)
                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                        {{ $event['status'] === 'completed' ? 'bg-green-500' : ($event['status'] === 'failed' ? 'bg-red-500' : 'bg-yellow-500') }}">
                                        @if($event['status'] === 'completed')
                                            <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        @elseif($event['status'] === 'failed')
                                            <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium">{{ $event['type'] }}</span>
                                        </p>
                                        <div class="mt-1 flex items-center space-x-2 text-xs text-gray-500">
                                            <span>{{ $event['delivery_count'] }} deliveries</span>
                                            @if($event['successful_deliveries'] > 0)
                                                <span class="text-green-600">{{ $event['successful_deliveries'] }} successful</span>
                                            @endif
                                            @if($event['failed_deliveries'] > 0)
                                                <span class="text-red-600">{{ $event['failed_deliveries'] }} failed</span>
                                            @endif
                                            @if($event['pending_deliveries'] > 0)
                                                <span class="text-yellow-600">{{ $event['pending_deliveries'] }} pending</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                        <time datetime="{{ $event['created_at'] }}">{{ $event['created_at']->diffForHumans() }}</time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        
        <div class="mt-4 text-center">
            <a href="{{ route('dashboard.events') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                View all events
                <span aria-hidden="true"> →</span>
            </a>
        </div>
    @else
        <div class="text-center py-6">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1h2a1 1 0 011 1v3" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No recent events</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first webhook subscription.</p>
        </div>
    @endif
</div>
