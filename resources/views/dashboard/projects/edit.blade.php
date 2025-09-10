@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
                <span>/</span>
                <a href="{{ route('dashboard.projects') }}" class="hover:text-gray-700">Projects</a>
                <span>/</span>
                <a href="{{ route('dashboard.projects.show', $project) }}" class="hover:text-gray-700">{{ $project->name }}</a>
                <span>/</span>
                <span class="text-gray-900">Edit</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900">Edit Project</h1>
            <p class="text-gray-600 mt-2">Update your project details and settings.</p>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('dashboard.projects.update', $project) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- Basic Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Project Name *</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter project name">
                            </div>
                            
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Project Slug *</label>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $project->slug) }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="project-slug">
                                <p class="text-sm text-gray-500 mt-1">Used in webhook URLs. Only lowercase letters, numbers, and hyphens.</p>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Describe what this project is for...">{{ old('description', $project->description) }}</textarea>
                        </div>
                    </div>

                    <!-- Project Status -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Project Status</h3>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" value="1" 
                                {{ old('is_active', $project->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Active (webhooks will be processed)
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">When inactive, webhook requests will be rejected.</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        <div>
                            <button type="button" onclick="confirmDelete()" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                Delete Project
                            </button>
                        </div>
                        
                        <div class="flex space-x-4">
                            <a href="{{ route('dashboard.projects.show', $project) }}"
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Update Project
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Delete Project</h3>
        <p class="text-sm text-gray-500 mb-6">
            Are you sure you want to delete this project? This action cannot be undone and will also delete all associated webhook endpoints and events.
        </p>
        <div class="flex justify-end space-x-4">
            <button type="button" onclick="closeDeleteModal()" 
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <form action="{{ route('dashboard.projects.destroy', $project) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete Project
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-generate slug from name (only if slug is empty)
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value;
        const slugField = document.getElementById('slug');
        if (!slugField.value || slugField.value === slugField.defaultValue) {
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            slugField.value = slug;
        }
    });

    // Delete confirmation
    function confirmDelete() {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    // Close modal on outside click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
</script>
@endsection