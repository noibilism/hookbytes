@extends('layouts.app')

@section('title', 'Edit Transformation')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Edit Transformation</h1>
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
        <form method="POST" action="{{ route('transformations.update', [$endpoint, $transformation]) }}" class="p-6">
            @csrf
            @method('PATCH')
            
            <div class="space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $transformation->name) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select id="type" name="type" required onchange="toggleTransformationConfig()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('type') border-red-500 @enderror">
                            <option value="field_mapping" {{ old('type', $transformation->type) === 'field_mapping' ? 'selected' : '' }}>Field Mapping</option>
                            <option value="template" {{ old('type', $transformation->type) === 'template' ? 'selected' : '' }}>Template</option>
                            <option value="javascript" {{ old('type', $transformation->type) === 'javascript' ? 'selected' : '' }}>JavaScript</option>
                            <option value="jq" {{ old('type', $transformation->type) === 'jq' ? 'selected' : '' }}>jq Filter</option>
                        </select>
                        @error('type')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <input type="number" id="priority" name="priority" min="1" max="100" value="{{ old('priority', $transformation->priority) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('priority') border-red-500 @enderror">
                        <p class="text-sm text-gray-500 mt-1">Lower numbers execute first</p>
                        @error('priority')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $transformation->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>

                <!-- Transformation Rules -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-4">Transformation Rules</label>
                    
                    <!-- Field Mapping Configuration -->
                    <div id="field-mapping-config" class="transformation-config @if(old('type', $transformation->type) !== 'field_mapping') hidden @endif">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-sm font-medium text-gray-700">Field Mappings</h4>
                                <button type="button" onclick="addFieldMapping()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                    Add Mapping
                                </button>
                            </div>
                            <div id="field-mappings">
                                @php
                                    $mappings = old('transformation_rules.mappings', $transformation->transformation_rules['mappings'] ?? []);
                                @endphp
                                @forelse($mappings as $index => $mapping)
                                    <div class="field-mapping-row flex gap-4 mb-3">
                                        <input type="text" name="transformation_rules[mappings][{{ $index }}][source]" 
                                               value="{{ $mapping['source'] ?? '' }}" placeholder="Source field (e.g., user.name)"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded">
                                        <input type="text" name="transformation_rules[mappings][{{ $index }}][target]" 
                                               value="{{ $mapping['target'] ?? '' }}" placeholder="Target field (e.g., customer_name)"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded">
                                        <button type="button" onclick="removeFieldMapping(this)" class="text-red-600 hover:text-red-800 px-2">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @empty
                                    <div class="field-mapping-row flex gap-4 mb-3">
                                        <input type="text" name="transformation_rules[mappings][0][source]" placeholder="Source field (e.g., user.name)"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded">
                                        <input type="text" name="transformation_rules[mappings][0][target]" placeholder="Target field (e.g., customer_name)"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded">
                                        <button type="button" onclick="removeFieldMapping(this)" class="text-red-600 hover:text-red-800 px-2">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Template Configuration -->
                    <div id="template-config" class="transformation-config @if(old('type', $transformation->type) !== 'template') hidden @endif">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label for="template-content" class="block text-sm font-medium text-gray-700 mb-2">Template (JSON with placeholders)</label>
                            <textarea id="template-content" name="transformation_rules[template]" rows="8" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm"
                                      placeholder='{"customer": "{{user.name}}", "email": "{{user.email}}"}'>{{ old('transformation_rules.template', $transformation->transformation_rules['template'] ?? '') }}</textarea>
                            <p class="text-sm text-gray-500 mt-2">Use {{field.path}} syntax for placeholders</p>
                        </div>
                    </div>

                    <!-- JavaScript Configuration -->
                    <div id="javascript-config" class="transformation-config @if(old('type', $transformation->type) !== 'javascript') hidden @endif">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label for="javascript-code" class="block text-sm font-medium text-gray-700 mb-2">JavaScript Code</label>
                            <textarea id="javascript-code" name="transformation_rules[code]" rows="8" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm"
                                      placeholder="// Transform the payload\nfunction transform(payload) {\n  return payload;\n}">{{ old('transformation_rules.code', $transformation->transformation_rules['code'] ?? '') }}</textarea>
                            <p class="text-sm text-gray-500 mt-2">Function should accept and return a payload object</p>
                        </div>
                    </div>

                    <!-- jq Configuration -->
                    <div id="jq-config" class="transformation-config @if(old('type', $transformation->type) !== 'jq') hidden @endif">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label for="jq-filter" class="block text-sm font-medium text-gray-700 mb-2">jq Filter</label>
                            <textarea id="jq-filter" name="transformation_rules[filter]" rows="4" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm"
                                      placeholder=".user | {customer_name: .name, customer_email: .email}">{{ old('transformation_rules.filter', $transformation->transformation_rules['filter'] ?? '') }}</textarea>
                            <p class="text-sm text-gray-500 mt-2">Use jq syntax to transform the payload</p>
                        </div>
                    </div>
                </div>

                <!-- Conditions (Optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conditions (Optional)</label>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <textarea name="conditions" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm"
                                  placeholder='{"field": "event_type", "operator": "equals", "value": "user.created"}'>{{ old('conditions', json_encode($transformation->conditions ?? [], JSON_PRETTY_PRINT)) }}</textarea>
                        <p class="text-sm text-gray-500 mt-2">JSON array of conditions that must be met for this transformation to apply</p>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('transformations.index', $endpoint) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                        Update Transformation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleTransformationConfig() {
    const type = document.getElementById('type').value;
    const configs = document.querySelectorAll('.transformation-config');
    
    configs.forEach(config => {
        config.classList.add('hidden');
    });
    
    const activeConfig = document.getElementById(type + '-config');
    if (activeConfig) {
        activeConfig.classList.remove('hidden');
    }
}

// Initialize mapping index based on existing mappings
let mappingIndex = document.querySelectorAll('#field-mappings .field-mapping-row').length;

function addFieldMapping() {
    const container = document.getElementById('field-mappings');
    const div = document.createElement('div');
    div.className = 'field-mapping-row flex gap-4 mb-3';
    div.innerHTML = `
        <input type="text" name="transformation_rules[mappings][${mappingIndex}][source]" placeholder="Source field (e.g., user.name)"
               class="flex-1 px-3 py-2 border border-gray-300 rounded">
        <input type="text" name="transformation_rules[mappings][${mappingIndex}][target]" placeholder="Target field (e.g., customer_name)"
               class="flex-1 px-3 py-2 border border-gray-300 rounded">
        <button type="button" onclick="removeFieldMapping(this)" class="text-red-600 hover:text-red-800 px-2">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
    mappingIndex++;
}

function removeFieldMapping(button) {
    const container = document.getElementById('field-mappings');
    if (container.children.length > 1) {
        button.closest('.field-mapping-row').remove();
    }
}
</script>
@endsection