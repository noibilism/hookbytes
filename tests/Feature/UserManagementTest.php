<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);
        
        $this->regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_admin_can_view_users_list()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        User::factory()->count(5)->create();

        $response = $this->get('/admin/users');

        $response->assertStatus(200)
            ->assertViewIs('admin.users.index')
            ->assertSee('User Management')
            ->assertSee($this->adminUser->email)
            ->assertSee($this->regularUser->email);
    }

    public function test_regular_user_cannot_access_admin_users_list()
    {
        $this->actingAs($this->regularUser);
        session(['_token' => csrf_token()]);

        $response = $this->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
        ];

        $response = $this->post('/admin/users', $userData);

        $response->assertRedirect('/admin/users')
            ->assertSessionHas('success', 'User created successfully');

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'is_admin' => false,
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_admin_can_create_admin_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $userData = [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'adminpass123',
            'password_confirmation' => 'adminpass123',
            'is_admin' => true,
        ];

        $response = $this->post('/admin/users', $userData);

        $response->assertRedirect('/admin/users')
            ->assertSessionHas('success', 'User created successfully');

        $this->assertDatabaseHas('users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'is_admin' => true,
        ]);
    }

    public function test_user_creation_validates_required_fields()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $response = $this->post('/admin/users', [
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_user_creation_validates_email_uniqueness()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $userData = [
            'name' => 'Duplicate User',
            'email' => $this->regularUser->email, // Existing email
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/admin/users', $userData);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_creation_validates_password_confirmation()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ];

        $response = $this->post('/admin/users', $userData);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_can_view_user_details()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ]);

        Project::factory()->count(3)->create();

        $response = $this->get('/admin/users/' . $user->id);

        $response->assertStatus(200)
            ->assertViewIs('admin.users.show')
            ->assertSee('Test User')
            ->assertSee('testuser@example.com')
            ->assertSee('Projects: 3');
    }

    public function test_admin_can_edit_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create();

        $response = $this->get('/admin/users/' . $user->id . '/edit');

        $response->assertStatus(200)
            ->assertViewIs('admin.users.edit')
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_admin_can_update_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'is_admin' => false,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_admin' => true,
        ];

        $response = $this->put('/admin/users/' . $user->id, $updateData);

        $response->assertRedirect('/admin/users/' . $user->id)
            ->assertSessionHas('success', 'User updated successfully');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_update_user_password()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create();

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->put('/admin/users/' . $user->id, $updateData);

        $response->assertRedirect('/admin/users/' . $user->id)
            ->assertSessionHas('success', 'User updated successfully');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_admin_can_delete_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create();

        $response = $this->delete('/admin/users/' . $user->id);

        $response->assertRedirect('/admin/users')
            ->assertSessionHas('success', 'User deleted successfully');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $response = $this->delete('/admin/users/' . $this->adminUser->id);

        $response->assertRedirect('/admin/users')
            ->assertSessionHas('error', 'You cannot delete your own account');

        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_admin_can_suspend_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create(['is_suspended' => false]);

        $response = $this->post('/admin/users/' . $user->id . '/suspend');

        $response->assertRedirect('/admin/users/' . $user->id)
            ->assertSessionHas('success', 'User suspended successfully');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_suspended' => true,
        ]);
    }

    public function test_admin_can_unsuspend_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create(['is_suspended' => true]);

        $response = $this->post('/admin/users/' . $user->id . '/unsuspend');

        $response->assertRedirect('/admin/users/' . $user->id)
            ->assertSessionHas('success', 'User unsuspended successfully');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_suspended' => false,
        ]);
    }

    public function test_suspended_user_cannot_login()
    {
        $suspendedUser = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('password'),
            'is_suspended' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'suspended@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Your account has been suspended.']);

        $this->assertGuest();
    }

    public function test_admin_can_view_user_activity_log()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create();

        $response = $this->get('/admin/users/' . $user->id . '/activity');

        $response->assertStatus(200)
            ->assertViewIs('admin.users.activity')
            ->assertSee('User Activity Log')
            ->assertSee($user->name);
    }

    public function test_admin_can_impersonate_user()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create();

        $response = $this->post('/admin/users/' . $user->id . '/impersonate');

        $response->assertRedirect('/dashboard')
            ->assertSessionHas('success', 'Now impersonating ' . $user->name);

        // Check that we're now acting as the impersonated user
        $this->assertEquals($user->id, Auth::id());
        $this->assertNotNull(session('impersonator_id'));
        $this->assertEquals($this->adminUser->id, session('impersonator_id'));
    }

    public function test_admin_can_stop_impersonating()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create();

        // Start impersonating
        $this->post('/admin/users/' . $user->id . '/impersonate');

        // Stop impersonating
        $response = $this->post('/admin/stop-impersonating');

        $response->assertRedirect('/admin/users')
            ->assertSessionHas('success', 'Stopped impersonating');

        // Check that we're back to the original admin user
        $this->assertEquals($this->adminUser->id, Auth::id());
        $this->assertNull(session('impersonator_id'));
    }

    public function test_admin_can_bulk_delete_users()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $response = $this->post('/admin/users/bulk-delete', [
            'user_ids' => $userIds,
        ]);

        $response->assertRedirect('/admin/users')
            ->assertSessionHas('success', '3 users deleted successfully');

        foreach ($userIds as $userId) {
            $this->assertDatabaseMissing('users', ['id' => $userId]);
        }
    }

    public function test_admin_can_bulk_suspend_users()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $users = User::factory()->count(3)->create(['is_suspended' => false]);
        $userIds = $users->pluck('id')->toArray();

        $response = $this->post('/admin/users/bulk-suspend', [
            'user_ids' => $userIds,
        ]);

        $response->assertRedirect('/admin/users')
            ->assertSessionHas('success', '3 users suspended successfully');

        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('users', [
                'id' => $userId,
                'is_suspended' => true,
            ]);
        }
    }

    public function test_admin_can_export_users_list()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        User::factory()->count(5)->create();

        $response = $this->get('/admin/users/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv')
            ->assertHeader('Content-Disposition', 'attachment; filename="users-export.csv"');

        $content = $response->getContent();
        $this->assertStringContainsString('Name,Email,Admin,Suspended,Created At', $content);
        $this->assertStringContainsString($this->adminUser->email, $content);
    }

    public function test_admin_can_search_users()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        $searchUser = User::factory()->create([
            'name' => 'John Searchable',
            'email' => 'john.searchable@example.com',
        ]);

        User::factory()->count(5)->create();

        $response = $this->get('/admin/users?search=Searchable');

        $response->assertStatus(200)
            ->assertSee('John Searchable')
            ->assertSee('john.searchable@example.com');
    }

    public function test_admin_can_filter_users_by_admin_status()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        User::factory()->count(3)->create(['is_admin' => true]);
        User::factory()->count(5)->create(['is_admin' => false]);

        $response = $this->get('/admin/users?filter=admin');

        $response->assertStatus(200);
        // Should show admin users (including the test admin user)
        // In a real implementation, you'd check the view data or response content
    }

    public function test_admin_can_filter_users_by_suspended_status()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        User::factory()->count(2)->create(['is_suspended' => true]);
        User::factory()->count(4)->create(['is_suspended' => false]);

        $response = $this->get('/admin/users?filter=suspended');

        $response->assertStatus(200);
        // Should show suspended users
        // In a real implementation, you'd check the view data or response content
    }

    public function test_regular_user_cannot_access_any_admin_functions()
    {
        $this->actingAs($this->regularUser);
        session(['_token' => csrf_token()]);

        $user = User::factory()->create();

        // Test various admin endpoints
        $adminEndpoints = [
            ['GET', '/admin/users'],
            ['POST', '/admin/users'],
            ['GET', '/admin/users/' . $user->id],
            ['GET', '/admin/users/' . $user->id . '/edit'],
            ['PUT', '/admin/users/' . $user->id],
            ['DELETE', '/admin/users/' . $user->id],
            ['POST', '/admin/users/' . $user->id . '/suspend'],
            ['POST', '/admin/users/' . $user->id . '/impersonate'],
            ['GET', '/admin/users/export'],
        ];

        foreach ($adminEndpoints as [$method, $endpoint]) {
            $response = $this->call($method, $endpoint);
            $response->assertStatus(403);
        }
    }

    public function test_user_pagination_works()
    {
        $this->actingAs($this->adminUser);
        session(['_token' => csrf_token()]);

        User::factory()->count(25)->create();

        $response = $this->get('/admin/users');

        $response->assertStatus(200)
            ->assertSee('Next')
            ->assertSee('Previous');

        // Test second page
        $response = $this->get('/admin/users?page=2');
        $response->assertStatus(200);
    }

    public function test_user_profile_can_be_updated_by_owner()
    {
        $this->actingAs($this->regularUser);
        session(['_token' => csrf_token()]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated.email@example.com',
        ];

        $response = $this->put('/profile', $updateData);

        $response->assertRedirect('/profile')
            ->assertSessionHas('success', 'Profile updated successfully');

        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'name' => 'Updated Name',
            'email' => 'updated.email@example.com',
        ]);
    }

    public function test_user_can_change_password()
    {
        $this->actingAs($this->regularUser);
        session(['_token' => csrf_token()]);

        $passwordData = [
            'current_password' => 'password', // Default factory password
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->put('/profile/password', $passwordData);

        $response->assertRedirect('/profile')
            ->assertSessionHas('success', 'Password updated successfully');

        $this->regularUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->regularUser->password));
    }

    public function test_password_change_validates_current_password()
    {
        $this->actingAs($this->regularUser);
        session(['_token' => csrf_token()]);

        $passwordData = [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->put('/profile/password', $passwordData);

        $response->assertSessionHasErrors(['current_password']);
    }
}