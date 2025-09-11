@extends('layouts.app')

@section('title', 'Webhook Transformations')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Webhook Transformations</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Endpoint: <span class="font-medium">{{ $endpoint->name }}</span>
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard.projects.show', $endpoint->project) }}" 
                       class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                        ← Back to Project
                    </a>
                    <a href="{{ route('transformations.create', $endpoint) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Add Transformation
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

        <!-- Transformations List -->
        <div class="p-6">
            @if($transformations->count() > 0)
                <div class="space-y-4">
                    @foreach($transformations as $transformation)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $transformation->name }}</h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($transformation->type === 'field_mapping') bg-blue-100 text-blue-800
                                            @elseif($transformation->type === 'template') bg-green-100 text-green-800
                                            @elseif($transformation->type === 'javascript') bg-yellow-100 text-yellow-800
                                            @else bg-purple-100 text-purple-800 @endif">
                                            {{ ucfirst(str_replace('_', ' ', $transformation->type)) }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($transformation->is_active) bg-green-100 text-green-800 @else bg-gray-100 text-gray-800 @endif">
                                            @if($transformation->is_active) Active @else Inactive @endif
                                        </span>
                                        <span class="text-sm text-gray-500">Priority: {{ $transformation->priority }}</span>
                                    </div>
                                    @if($transformation->last_tested_at)
                                        <p class="text-sm text-gray-600 mt-1">
                                            Last tested: {{ $transformation->last_tested_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    <!-- Toggle Active Status -->
                                    <button onclick="toggleTransformation({{ $transformation->id }}, {{ $transformation->is_active ? 'false' : 'true' }})"
                                            class="text-sm px-3 py-1 rounded border transition-colors
                                            @if($transformation->is_active) border-red-300 text-red-700 hover:bg-red-50 @else border-green-300 text-green-700 hover:bg-green-50 @endif">
                                        @if($transformation->is_active) Disable @else Enable @endif
                                    </button>
                                    
                                    <!-- Test Button -->
                                    <button onclick="openTestModal({{ $transformation->id }}, '{{ addslashes($transformation->name) }}')"
                                            class="text-sm px-3 py-1 rounded border border-blue-300 text-blue-700 hover:bg-blue-50 transition-colors">
                                        Test
                                    </button>
                                    
                                    <!-- Duplicate -->
                                    <form method="POST" action="{{ route('transformations.duplicate', [$endpoint, $transformation]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                                            Duplicate
                                        </button>
                                    </form>
                                    
                                    <!-- Edit -->
                                    <a href="{{ route('transformations.edit', [$endpoint, $transformation]) }}"
                                       class="text-sm px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                                        Edit
                                    </a>
                                    
                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('transformations.destroy', [$endpoint, $transformation]) }}" 
                                          class="inline" onsubmit="return confirm('Are you sure you want to delete this transformation?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm px-3 py-1 rounded border border-red-300 text-red-700 hover:bg-red-50 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No transformations</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first transformation.</p>
                    <div class="mt-6">
                        <a href="{{ route('transformations.create', $endpoint) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Add Transformation
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Test Modal -->
<div id="testModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Test Transformation</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Test Payload (JSON)</label>
                    <textarea id="testPayload" rows="8" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" 
                              placeholder='{"example": "data"}'></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Test Headers (JSON, optional)</label>
                    <textarea id="testHeaders" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" 
                              placeholder='{"Content-Type": "application/json"}'></textarea>
                </div>
                <div id="testResult" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Result</label>
                    <pre id="testResultContent" class="bg-gray-100 p-3 rounded text-sm overflow-auto max-h-64"></pre>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="closeTestModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Cancel
                </button>
                <button onclick="runTest()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Run Test
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTransformationId = null;

function toggleTransformation(transformationId, newStatus) {
    fetch(`/dashboard/endpoints/{{ $endpoint->id }}/transformations/${transformationId}/toggle`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
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
        alert('Failed to toggle transformation status');
    });
}

function openTestModal(transformationId, transformationName) {
    currentTransformationId = transformationId;
    document.querySelector('#testModal h3').textContent = `Test Transformation: ${transformationName}`;
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testResult').classList.add('hidden');
    document.getElementById('testPayload').value = '';
    document.getElementById('testHeaders').value = '';
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
    currentTransformationId = null;
}

function runTest() {
    const payload = document.getElementById('testPayload').value;
    const headers = document.getElementById('testHeaders').value;
    
    let testPayload, testHeaders;
    
    try {
        testPayload = JSON.parse(payload || '{}');
        testHeaders = headers ? JSON.parse(headers) : {};
    } catch (e) {
        alert('Invalid JSON format');
        return;
    }
    
    fetch(`/dashboard/endpoints/{{ $endpoint->id }}/transformations/${currentTransformationId}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            test_payload: testPayload,
            test_headers: testHeaders
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('testResult').classList.remove('hidden');
        document.getElementById('testResultContent').textContent = JSON.stringify(data, null, 2);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to run test');
    });
}

// Close modal when clicking outside
document.getElementById('testModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTestModal();
    }
});
</script>
@endsection