@extends('layouts.master')

@section('title', 'Create Routing Rule - HookBytes Dashboard')

@section('content')
<!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Create Routing Rule</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Endpoint: <span class="font-medium">{{ $endpoint->name }}</span>
                    </p>
                </div>
                <a href="{{ route('routing-rules.index', $endpoint) }}" 
                   class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                    ← Back to Routing Rules
                </a>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('routing-rules.store', $endpoint) }}" class="p-6">
            @csrf
            
            <!-- Basic Information -->
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Rule Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., Payment Success Router" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <input type="number" name="priority" id="priority" value="{{ old('priority', 10) }}"
                               min="1" max="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="1-100 (lower = higher priority)" required>
                        @error('priority')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Lower numbers have higher priority (1 = highest)</p>
                    </div>
                </div>
                
                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Optional description of what this rule does">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Action -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="action" value="route" 
                                   {{ old('action', 'route') === 'route' ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" 
                                   onchange="toggleDestinations()">
                            <span class="ml-2 text-sm text-gray-700">Route - Forward to destinations</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="action" value="drop" 
                                   {{ old('action') === 'drop' ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                   onchange="toggleDestinations()">
                            <span class="ml-2 text-sm text-gray-700">Drop - Ignore the event</span>
                        </label>
                    </div>
                    @error('action')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Active Status -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Active (rule will be applied)</label>
                </div>
            </div>
            
            <!-- Conditions Section -->
            <div class="mt-8">
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Conditions</h3>
                    <p class="text-sm text-gray-600 mb-4">Define when this rule should be applied. All conditions must match.</p>
                    
                    <div id="conditions-container">
                        <!-- Conditions will be added here dynamically -->
                    </div>
                    
                    <button type="button" onclick="addCondition()" 
                            class="mt-4 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Condition
                    </button>
                </div>
            </div>
            
            <!-- Destinations Section -->
            <div id="destinations-section" class="mt-8">
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Destinations</h3>
                    <p class="text-sm text-gray-600 mb-4">Define where matching events should be forwarded.</p>
                    
                    <div id="destinations-container">
                        <!-- Destinations will be added here dynamically -->
                    </div>
                    
                    <button type="button" onclick="addDestination()" 
                            class="mt-4 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Destination
                    </button>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="mt-8 border-t border-gray-200 pt-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Examples</h4>
                    <div class="text-sm text-blue-800 space-y-1">
                        <p><strong>Condition:</strong> {{{ "{{payload.event.type}}" }}} equals "payment.success"</p>
                        <p><strong>Condition:</strong> {{{ "{{payload.amount}}" }}} greater than "100"</p>
                        <p><strong>Condition:</strong> {{{ "{{headers.user-agent}}" }}} contains "webhook"</p>
                    </div>
                </div>
            </div>
            
            <!-- Submit Buttons -->
            <div class="mt-8 flex items-center justify-end space-x-3">
                <a href="{{ route('routing-rules.index', $endpoint) }}"
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create Routing Rule
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let conditionIndex = 0;
let destinationIndex = 0;

function addCondition() {
    const container = document.getElementById('conditions-container');
    const conditionHtml = `
        <div class="condition-item border border-gray-200 rounded-lg p-4 mb-4" data-index="${conditionIndex}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Path</label>
                    <input type="text" name="conditions[${conditionIndex}][field]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                           placeholder="payload.event.type">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Operator</label>
                    <select name="conditions[${conditionIndex}][operator]" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="=">equals</option>
                        <option value="!=">not equals</option>
                        <option value=">">&gt; greater than</option>
                        <option value="<">&lt; less than</option>
                        <option value=">=">&gt;= greater or equal</option>
                        <option value="<=">&lt;= less or equal</option>
                        <option value="contains">contains</option>
                        <option value="starts_with">starts with</option>
                        <option value="ends_with">ends with</option>
                        <option value="in">in array</option>
                        <option value="not_in">not in array</option>
                        <option value="exists">exists</option>
                        <option value="not_exists">not exists</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                    <input type="text" name="conditions[${conditionIndex}][value]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                           placeholder="payment.success">
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="removeCondition(${conditionIndex})" 
                            class="px-3 py-2 text-sm text-red-600 hover:text-red-700 font-medium">
                        Remove
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', conditionHtml);
    conditionIndex++;
}

function removeCondition(index) {
    const element = document.querySelector(`[data-index="${index}"]`);
    if (element) {
        element.remove();
    }
}

function addDestination() {
    const container = document.getElementById('destinations-container');
    const destinationHtml = `
        <div class="destination-item border border-gray-200 rounded-lg p-4 mb-4" data-index="${destinationIndex}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destination URL</label>
                    <input type="url" name="destinations[${destinationIndex}][url]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                           placeholder="https://api.example.com/webhook" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="destinations[${destinationIndex}][priority]" 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                               placeholder="10" min="1" max="100">
                        <button type="button" onclick="removeDestination(${destinationIndex})" 
                                class="px-3 py-2 text-sm text-red-600 hover:text-red-700 font-medium">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', destinationHtml);
    destinationIndex++;
}

function removeDestination(index) {
    const element = document.querySelector(`[data-index="${index}"].destination-item`);
    if (element) {
        element.remove();
    }
}

function toggleDestinations() {
    const action = document.querySelector('input[name="action"]:checked').value;
    const destinationsSection = document.getElementById('destinations-section');
    
    if (action === 'drop') {
        destinationsSection.style.display = 'none';
    } else {
        destinationsSection.style.display = 'block';
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Add initial condition
    addCondition();
    
    // Add initial destination if action is route
    const routeAction = document.querySelector('input[name="action"][value="route"]');
    if (routeAction && routeAction.checked) {
        addDestination();
    }
    
    // Set initial visibility
    toggleDestinations();
});
</script>
@endpush