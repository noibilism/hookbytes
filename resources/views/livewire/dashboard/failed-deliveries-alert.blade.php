<div>
    @if($showAlert)
        <div class="rounded-md bg-red-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-red-800">
                        @if($criticalFailures > 0)
                            Critical: {{ $criticalFailures }} delivery failures in the last hour
                        @else
                            Warning: {{ $recentFailures }} delivery failures in the last 24 hours
                        @endif
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>
                            @if($criticalFailures > 0)
                                Multiple webhook deliveries have failed recently. This may indicate an issue with your endpoint or network connectivity.
                            @else
                                You have a high number of failed deliveries. Consider checking your webhook endpoints and reviewing the delivery logs.
                            @endif
                        </p>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <a href="{{ route('dashboard.deliveries', ['status' => 'failed']) }}" class="rounded-md bg-red-50 px-2 py-1.5 text-sm font-medium text-red-800 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50">
                                View failed deliveries
                            </a>
                            <button wire:click="dismissAlert" type="button" class="ml-3 rounded-md bg-red-50 px-2 py-1.5 text-sm font-medium text-red-800 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50">
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
