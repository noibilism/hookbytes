@extends('layouts.app')

@section('title', 'Create Transformation')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Create Transformation</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Endpoint: <span class="font-medium">{{ $endpoint->name }}</span>
                    </p>
                </div>
                <a href="{{ route('transformations.index', $endpoint) }}" 
                   class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                    ← Back to Transformations
                </a>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('transformations.store', $endpoint) }}" class="p-6">
            @csrf
            
            <div class="space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Format User Data">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select id="type" name="type" required onchange="updateTransformationForm()"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select transformation type</option>
                            <option value="field_mapping" {{ old('type') === 'field_mapping' ? 'selected' : '' }}>Field Mapping</option>
                            <option value="template" {{ old('type') === 'template' ? 'selected' : '' }}>Template</option>
                            <option value="javascript" {{ old('type') === 'javascript' ? 'selected' : '' }}>JavaScript (Coming Soon)</option>
                            <option value="jq" {{ old('type') === 'jq' ? 'selected' : '' }}>jq Filter (Coming Soon)</option>
                        </select>
                        @error('type')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Priority and Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <input type="number" id="priority" name="priority" value="{{ old('priority', 10) }}" min="1" max="100" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Lower numbers = higher priority (1-100)</p>
                        @error('priority')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                    </div>
                </div>

                <!-- Transformation Rules -->
                <div id="transformation-rules">
                    <!-- Field Mapping Form -->
                    <div id="field-mapping-form" class="hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Field Mapping Configuration</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="merge_with_original" name="transformation_rules[merge_with_original]" value="1"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="merge_with_original" class="ml-2 block text-sm text-gray-900">Merge with original payload</label>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Field Mappings</label>
                                <div id="field-mappings">
                                    <div class="field-mapping-row grid grid-cols-12 gap-2 items-end mb-2">
                                        <div class="col-span-3">
                                            <label class="block text-xs text-gray-600 mb-1">Source Field</label>
                                            <input type="text" name="transformation_rules[mappings][0][source]" 
                                                   placeholder="e.g., user.name"
                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                        </div>
                                        <div class="col-span-3">
                                            <label class="block text-xs text-gray-600 mb-1">Target Field</label>
                                            <input type="text" name="transformation_rules[mappings][0][target]" 
                                                   placeholder="e.g., customer.full_name"
                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs text-gray-600 mb-1">Transform</label>
                                            <select name="transformation_rules[mappings][0][transform]" 
                                                    class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                                <option value="">None</option>
                                                <option value="uppercase">Uppercase</option>
                                                <option value="lowercase">Lowercase</option>
                                                <option value="trim">Trim</option>
                                                <option value="to_string">To String</option>
                                                <option value="to_int">To Integer</option>
                                                <option value="to_float">To Float</option>
                                                <option value="to_bool">To Boolean</option>
                                            </select>
                                        </div>
                                        <div class="col-span-3">
                                            <label class="block text-xs text-gray-600 mb-1">Default Value</label>
                                            <input type="text" name="transformation_rules[mappings][0][default]" 
                                                   placeholder="Optional default"
                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                        </div>
                                        <div class="col-span-1">
                                            <button type="button" onclick="removeFieldMapping(this)" 
                                                    class="text-red-600 hover:text-red-800 text-sm px-2 py-1">
                                                ×
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" onclick="addFieldMapping()" 
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    + Add Field Mapping
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Template Form -->
                    <div id="template-form" class="hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Template Configuration</h3>
                        
                        <div>
                            <label for="template" class="block text-sm font-medium text-gray-700 mb-2">JSON Template</label>
                            <textarea id="template" name="transformation_rules[template]" rows="12"
                                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder='{
  "id": "{{{ "{{payload.user.id}}" }}}",
  "name": "{{{ "{{payload.user.first_name}} {{payload.user.last_name}}" }}}",
  "email": "{{{ "{{payload.user.email}}" }}}",
  "timestamp": "{{{ "{{timestamp}}" }}}",
  "custom_field": "static_value"
}'></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                Use {{{ "{{payload.field.path}}" }}} to access payload data, {{{ "{{headers.header_name}}" }}} for headers, {{{ "{{timestamp}}" }}} for current time
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Conditions (Optional) -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Conditions (Optional)</h3>
                    <p class="text-sm text-gray-600 mb-4">Only apply this transformation when certain conditions are met</p>
                    
                    <div id="conditions">
                        <div class="condition-row grid grid-cols-12 gap-2 items-end mb-2">
                            <div class="col-span-4">
                                <label class="block text-xs text-gray-600 mb-1">Field Path</label>
                                <input type="text" name="conditions[0][field]" 
                                       placeholder="e.g., event_type"
                                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-600 mb-1">Operator</label>
                                <select name="conditions[0][operator]" 
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                    <option value="equals">Equals</option>
                                    <option value="not_equals">Not Equals</option>
                                    <option value="contains">Contains</option>
                                    <option value="starts_with">Starts With</option>
                                    <option value="ends_with">Ends With</option>
                                    <option value="exists">Exists</option>
                                    <option value="not_exists">Not Exists</option>
                                </select>
                            </div>
                            <div class="col-span-5">
                                <label class="block text-xs text-gray-600 mb-1">Value</label>
                                <input type="text" name="conditions[0][value]" 
                                       placeholder="Expected value"
                                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                            </div>
                            <div class="col-span-1">
                                <button type="button" onclick="removeCondition(this)" 
                                        class="text-red-600 hover:text-red-800 text-sm px-2 py-1">
                                    ×
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addCondition()" 
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        + Add Condition
                    </button>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('transformations.index', $endpoint) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                        Create Transformation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let fieldMappingIndex = 1;
let conditionIndex = 1;

function updateTransformationForm() {
    const type = document.getElementById('type').value;
    
    // Hide all forms
    document.getElementById('field-mapping-form').classList.add('hidden');
    document.getElementById('template-form').classList.add('hidden');
    
    // Show relevant form
    if (type === 'field_mapping') {
        document.getElementById('field-mapping-form').classList.remove('hidden');
    } else if (type === 'template') {
        document.getElementById('template-form').classList.remove('hidden');
    }
}

function addFieldMapping() {
    const container = document.getElementById('field-mappings');
    const newRow = document.createElement('div');
    newRow.className = 'field-mapping-row grid grid-cols-12 gap-2 items-end mb-2';
    newRow.innerHTML = `
        <div class="col-span-3">
            <input type="text" name="transformation_rules[mappings][${fieldMappingIndex}][source]" 
                   placeholder="e.g., user.name"
                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div class="col-span-3">
            <input type="text" name="transformation_rules[mappings][${fieldMappingIndex}][target]" 
                   placeholder="e.g., customer.full_name"
                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div class="col-span-2">
            <select name="transformation_rules[mappings][${fieldMappingIndex}][transform]" 
                    class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                <option value="">None</option>
                <option value="uppercase">Uppercase</option>
                <option value="lowercase">Lowercase</option>
                <option value="trim">Trim</option>
                <option value="to_string">To String</option>
                <option value="to_int">To Integer</option>
                <option value="to_float">To Float</option>
                <option value="to_bool">To Boolean</option>
            </select>
        </div>
        <div class="col-span-3">
            <input type="text" name="transformation_rules[mappings][${fieldMappingIndex}][default]" 
                   placeholder="Optional default"
                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div class="col-span-1">
            <button type="button" onclick="removeFieldMapping(this)" 
                    class="text-red-600 hover:text-red-800 text-sm px-2 py-1">
                ×
            </button>
        </div>
    `;
    container.appendChild(newRow);
    fieldMappingIndex++;
}

function removeFieldMapping(button) {
    button.closest('.field-mapping-row').remove();
}

function addCondition() {
    const container = document.getElementById('conditions');
    const newRow = document.createElement('div');
    newRow.className = 'condition-row grid grid-cols-12 gap-2 items-end mb-2';
    newRow.innerHTML = `
        <div class="col-span-4">
            <input type="text" name="conditions[${conditionIndex}][field]" 
                   placeholder="e.g., event_type"
                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div class="col-span-2">
            <select name="conditions[${conditionIndex}][operator]" 
                    class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                <option value="equals">Equals</option>
                <option value="not_equals">Not Equals</option>
                <option value="contains">Contains</option>
                <option value="starts_with">Starts With</option>
                <option value="ends_with">Ends With</option>
                <option value="exists">Exists</option>
                <option value="not_exists">Not Exists</option>
            </select>
        </div>
        <div class="col-span-5">
            <input type="text" name="conditions[${conditionIndex}][value]" 
                   placeholder="Expected value"
                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div class="col-span-1">
            <button type="button" onclick="removeCondition(this)" 
                    class="text-red-600 hover:text-red-800 text-sm px-2 py-1">
                ×
            </button>
        </div>
    `;
    container.appendChild(newRow);
    conditionIndex++;
}

function removeCondition(button) {
    button.closest('.condition-row').remove();
}

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    updateTransformationForm();
});
</script>
@endsection