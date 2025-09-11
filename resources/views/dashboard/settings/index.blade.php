@extends('layouts.master')

@section('title', 'Settings - HookBytes Dashboard')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'JetBrains Mono', monospace;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
@endpush

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Main Content -->
        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Notification Settings</h1>
                <p class="text-gray-600">Configure Slack and email notifications for failed webhook events.</p>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <!-- Settings Form -->
            <div class="bg-white shadow-sm rounded-lg">
                <form action="{{ route('dashboard.settings.update') }}" method="POST" class="p-6">
                    @csrf
                    
                    <div class="space-y-8">
                        <!-- Slack Notifications -->
                        <div class="border-b border-gray-200 pb-8">
                            <div class="flex items-center mb-4">
                                <i class="fab fa-slack text-2xl text-purple-600 mr-3"></i>
                                <h2 class="text-lg font-semibold text-gray-900">Slack Notifications</h2>
                            </div>
                            <p class="text-gray-600 mb-6">Send notifications to Slack when webhook events fail.</p>
                            
                            <div class="space-y-4">
                                <!-- Enable Slack Notifications -->
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           id="slack_notifications_enabled" 
                                           name="slack_notifications_enabled" 
                                           value="1"
                                           {{ $settings->slack_notifications_enabled ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="slack_notifications_enabled" class="ml-2 block text-sm text-gray-900">
                                        Enable Slack notifications
                                    </label>
                                </div>
                                
                                <!-- Slack Webhook URL -->
                                <div>
                                    <label for="slack_webhook_url" class="block text-sm font-medium text-gray-700 mb-2">
                                        Slack Webhook URL
                                    </label>
                                    <input type="url" 
                                           id="slack_webhook_url" 
                                           name="slack_webhook_url" 
                                           value="{{ old('slack_webhook_url', $settings->slack_webhook_url) }}"
                                           placeholder="https://hooks.slack.com/services/..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    @error('slack_webhook_url')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">
                                        Get your webhook URL from Slack: Workspace Settings → Apps → Incoming Webhooks
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Email Notifications -->
                        <div>
                            <div class="flex items-center mb-4">
                                <i class="fas fa-envelope text-2xl text-blue-600 mr-3"></i>
                                <h2 class="text-lg font-semibold text-gray-900">Email Notifications</h2>
                            </div>
                            <p class="text-gray-600 mb-6">Send email notifications when webhook events fail.</p>
                            
                            <div class="space-y-4">
                                <!-- Enable Email Notifications -->
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           id="email_notifications_enabled" 
                                           name="email_notifications_enabled" 
                                           value="1"
                                           {{ $settings->email_notifications_enabled ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="email_notifications_enabled" class="ml-2 block text-sm text-gray-900">
                                        Enable email notifications
                                    </label>
                                </div>
                                
                                <!-- Notification Email -->
                                <div>
                                    <label for="notification_email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Notification Email
                                    </label>
                                    <input type="email" 
                                           id="notification_email" 
                                           name="notification_email" 
                                           value="{{ old('notification_email', $settings->notification_email) }}"
                                           placeholder="admin@example.com"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    @error('notification_email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">
                                        Email address to receive failure notifications
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Management -->
                        <div class="border-t border-gray-200 pt-8">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-users text-2xl text-green-600 mr-3"></i>
                                <h2 class="text-lg font-semibold text-gray-900">User Management</h2>
                            </div>
                            <p class="text-gray-600 mb-6">Manage users who have access to this dashboard.</p>
                            
                            <!-- Add User Button -->
                            <div class="mb-6">
                                <button type="button" onclick="toggleAddUserForm()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add New User
                                </button>
                            </div>
                            
                            <!-- Add User Form (Hidden by default) -->
                            <div id="addUserForm" class="hidden mb-6 p-4 bg-gray-50 rounded-lg border">
                                <h3 class="text-md font-medium text-gray-900 mb-4">Add New User</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="new_user_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                        <input type="text" id="new_user_name" name="new_user_name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="John Doe">
                                    </div>
                                    <div>
                                        <label for="new_user_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" id="new_user_email" name="new_user_email" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="john@example.com">
                                    </div>
                                    <div>
                                        <label for="new_user_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                        <input type="password" id="new_user_password" name="new_user_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Password">
                                    </div>
                                    <div>
                                        <label for="new_user_role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                        <select id="new_user_role" name="new_user_role" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-4 flex space-x-3">
                                    <button type="button" onclick="addUser()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                        <i class="fas fa-save mr-2"></i>
                                        Add User
                                    </button>
                                    <button type="button" onclick="toggleAddUserForm()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Users List -->
                            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                    <h3 class="text-sm font-medium text-gray-900">Current Users</h3>
                                </div>
                                <div id="usersList" class="divide-y divide-gray-200">
                                    <!-- Users will be loaded here via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Save Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Toggle Add User Form
        function toggleAddUserForm() {
            const form = document.getElementById('addUserForm');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                document.getElementById('new_user_name').focus();
            }
        }
        
        // Load Users
        function loadUsers() {
            fetch('/dashboard/users')
                .then(response => response.json())
                .then(users => {
                    const usersList = document.getElementById('usersList');
                    usersList.innerHTML = '';
                    
                    users.forEach(user => {
                        const userRow = document.createElement('div');
                        userRow.className = 'px-4 py-3 flex items-center justify-between';
                        userRow.innerHTML = `
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600 text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">${user.name}</p>
                                    <p class="text-sm text-gray-500">${user.email}</p>
                                </div>
                                <div class="ml-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                        user.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'
                                    }">
                                        ${user.role || 'user'}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="editUser(${user.id})" class="text-blue-600 hover:text-blue-900 text-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                ${user.id !== 1 ? `
                                    <button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-900 text-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                ` : ''}
                            </div>
                        `;
                        usersList.appendChild(userRow);
                    });
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                });
        }
        
        // Add User
        function addUser() {
            const name = document.getElementById('new_user_name').value;
            const email = document.getElementById('new_user_email').value;
            const password = document.getElementById('new_user_password').value;
            const role = document.getElementById('new_user_role').value;
            
            if (!name || !email || !password) {
                alert('Please fill in all required fields.');
                return;
            }
            
            fetch('/dashboard/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password,
                    role: role
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear form
                    document.getElementById('new_user_name').value = '';
                    document.getElementById('new_user_email').value = '';
                    document.getElementById('new_user_password').value = '';
                    document.getElementById('new_user_role').value = 'user';
                    
                    // Hide form
                    toggleAddUserForm();
                    
                    // Reload users
                    loadUsers();
                    
                    alert('User added successfully!');
                } else {
                    alert('Error adding user: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error adding user:', error);
                alert('Error adding user. Please try again.');
            });
        }
        
        // Edit User
        function editUser(userId) {
            // For now, just show an alert. This can be expanded later.
            alert('Edit user functionality will be implemented in a future update.');
        }
        
        // Delete User
        function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user?')) {
                return;
            }
            
            fetch(`/dashboard/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadUsers();
                    alert('User deleted successfully!');
                } else {
                    alert('Error deleting user: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                alert('Error deleting user. Please try again.');
            });
        }
        
        // Load users when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });
    </script>
                </div>
            </div>
        </div>
    </div>
    </script>
@endpush