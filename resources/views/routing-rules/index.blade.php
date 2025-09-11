@extends('layouts.master')

@section('title', 'Routing Rules - HookBytes Dashboard')

@section('content')
<!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Webhook Routing Rules</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Endpoint: <span class="font-medium">{{ $endpoint->name }}</span>
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard.projects.show', $endpoint->project) }}" 
                       class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                        ← Back to Project
                    </a>
                    <a href="{{ route('routing-rules.create', $endpoint) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Add Routing Rule
                    </a>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mx-6 mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Content -->
        <div class="p-6">
            @if($routingRules->count() > 0)
                <!-- Rules List -->
                <div class="space-y-4">
                    @foreach($routingRules as $rule)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $rule->name }}</h3>
                                        
                                        <!-- Action Badge -->
                                        @if($rule->action === 'route')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Route
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Drop
                                            </span>
                                        @endif
                                        
                                        <!-- Status Badge -->
                                        @if($rule->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        @endif
                                        
                                        <!-- Priority Badge -->
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Priority: {{ $rule->priority }}
                                        </span>
                                    </div>
                                    
                                    @if($rule->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $rule->description }}</p>
                                    @endif
                                    
                                    <!-- Conditions Summary -->
                                    @if(!empty($rule->conditions))
                                        <div class="mt-2">
                                            <p class="text-xs text-gray-500 font-medium">Conditions:</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($rule->conditions as $condition)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                                                        {{ $condition['field'] ?? 'field' }} {{ $condition['operator'] ?? '=' }} {{ $condition['value'] ?? 'value' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Destinations Summary -->
                                    @if($rule->action === 'route' && !empty($rule->destinations))
                                        <div class="mt-2">
                                            <p class="text-xs text-gray-500 font-medium">Destinations ({{ count($rule->destinations) }}):</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach(array_slice($rule->destinations, 0, 3) as $destination)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">
                                                        {{ is_array($destination) ? $destination['url'] : $destination }}
                                                    </span>
                                                @endforeach
                                                @if(count($rule->destinations) > 3)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                                                        +{{ count($rule->destinations) - 3 }} more
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Stats -->
                                    @if($rule->match_count > 0)
                                        <div class="mt-2 text-xs text-gray-500">
                                            Matched {{ $rule->match_count }} times
                                            @if($rule->last_matched_at)
                                                • Last matched {{ $rule->last_matched_at->diffForHumans() }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center space-x-2 ml-4">
                                    <!-- Toggle Active -->
                                    <button data-rule-id="{{ $rule->id }}" onclick="toggleRuleStatus(this.dataset.ruleId)"
                                            class="text-sm {{ $rule->is_active ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700' }} font-medium">
                                        {{ $rule->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    
                                    <!-- Edit -->
                                    <a href="{{ route('routing-rules.edit', [$endpoint, $rule]) }}"
                                       class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                        Edit
                                    </a>
                                    
                                    <!-- Duplicate -->
                                    <form method="POST" action="{{ route('routing-rules.duplicate', [$endpoint, $rule]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                            Duplicate
                                        </button>
                                    </form>
                                    
                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('routing-rules.destroy', [$endpoint, $rule]) }}" class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this routing rule?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-medium">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No routing rules</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first routing rule.</p>
                    <div class="mt-6">
                        <a href="{{ route('routing-rules.create', $endpoint) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Add Routing Rule
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
function deleteRule(ruleId) {
    if (confirm('Are you sure you want to delete this routing rule?')) {
        fetch(`/routing-rules/${ruleId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete routing rule');
        });
    }
}

function toggleRule(ruleId, isActive) {
    const url = '/dashboard/endpoints/{{ $endpoint->id }}/routing-rules/' + ruleId + '/toggle';
    fetch(url, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ is_active: isActive })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to toggle routing rule status');
    });
}
</script>
@endpush