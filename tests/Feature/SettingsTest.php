<?php

namespace Tests\Feature;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        session(['_token' => csrf_token()]);
    }

    public function test_settings_page_displays_correctly()
    {
        $response = $this->get('/dashboard/settings');

        $response->assertStatus(200)
            ->assertViewIs('dashboard.settings')
            ->assertSee('Application Settings')
            ->assertSee('General Settings')
            ->assertSee('Notification Settings')
            ->assertSee('Security Settings');
    }

    public function test_general_settings_can_be_updated()
    {
        $settingsData = [
            'app_name' => 'My Webhook Gateway',
            'app_description' => 'Custom webhook management system',
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ];

        $response = $this->put('/dashboard/settings/general', $settingsData);

        $response->assertRedirect('/dashboard/settings')
            ->assertSessionHas('success', 'General settings updated successfully');

        foreach ($settingsData as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    public function test_notification_settings_can_be_updated()
    {
        $settingsData = [
            'email_notifications' => true,
            'webhook_failure_notifications' => true,
            'daily_summary_notifications' => false,
            'notification_email' => 'admin@example.com',
            'slack_webhook_url' => 'https://hooks.slack.com/services/xxx',
            'discord_webhook_url' => '',
        ];

        $response = $this->put('/dashboard/settings/notifications', $settingsData);

        $response->assertRedirect('/dashboard/settings')
            ->assertSessionHas('success', 'Notification settings updated successfully');

        foreach ($settingsData as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : $value,
            ]);
        }
    }

    public function test_security_settings_can_be_updated()
    {
        $settingsData = [
            'require_https' => true,
            'api_rate_limit' => 1000,
            'webhook_timeout' => 30,
            'max_payload_size' => 1048576, // 1MB
            'allowed_ip_addresses' => '192.168.1.0/24,10.0.0.0/8',
            'enable_cors' => true,
            'cors_origins' => 'https://example.com,https://app.example.com',
        ];

        $response = $this->put('/dashboard/settings/security', $settingsData);

        $response->assertRedirect('/dashboard/settings')
            ->assertSessionHas('success', 'Security settings updated successfully');

        foreach ($settingsData as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string)$value,
            ]);
        }
    }

    public function test_webhook_settings_can_be_updated()
    {
        $settingsData = [
            'default_retry_attempts' => 5,
            'default_retry_delay' => 120,
            'default_timeout' => 30,
            'enable_webhook_signatures' => true,
            'signature_algorithm' => 'sha256',
            'enable_event_logging' => true,
            'log_retention_days' => 90,
        ];

        $response = $this->put('/settings/webhooks', $settingsData);

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Webhook settings updated successfully');

        foreach ($settingsData as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string)$value,
            ]);
        }
    }

    public function test_settings_validation_works()
    {
        $invalidData = [
            'app_name' => '', // Required
            'timezone' => 'Invalid/Timezone',
            'api_rate_limit' => -1, // Must be positive
            'webhook_timeout' => 0, // Must be greater than 0
            'notification_email' => 'invalid-email',
        ];

        $response = $this->put('/settings/general', $invalidData);

        $response->assertSessionHasErrors([
            'app_name',
            'timezone',
        ]);

        $response = $this->put('/settings/security', [
            'api_rate_limit' => -1,
            'webhook_timeout' => 0,
        ]);

        $response->assertSessionHasErrors([
            'api_rate_limit',
            'webhook_timeout',
        ]);

        $response = $this->put('/settings/notifications', [
            'notification_email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['notification_email']);
    }

    public function test_settings_can_be_reset_to_defaults()
    {
        // First, create some custom settings
        Settings::create(['key' => 'app_name', 'value' => 'Custom Name']);
        Settings::create(['key' => 'timezone', 'value' => 'Europe/London']);

        $response = $this->post('/settings/reset');

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Settings reset to defaults successfully');

        // Verify default values are restored
        $this->assertDatabaseHas('settings', [
            'key' => 'app_name',
            'value' => 'HookBytes Webhook Gateway',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'timezone',
            'value' => 'UTC',
        ]);
    }

    public function test_settings_export_works()
    {
        Settings::create(['key' => 'app_name', 'value' => 'Test App']);
        Settings::create(['key' => 'timezone', 'value' => 'UTC']);
        Settings::create(['key' => 'email_notifications', 'value' => '1']);

        $response = $this->get('/settings/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Disposition', 'attachment; filename="settings-export.json"');

        $exportData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('app_name', $exportData);
        $this->assertArrayHasKey('timezone', $exportData);
        $this->assertArrayHasKey('email_notifications', $exportData);
        $this->assertEquals('Test App', $exportData['app_name']);
    }

    public function test_settings_import_works()
    {
        $importData = [
            'app_name' => 'Imported App',
            'timezone' => 'America/Chicago',
            'email_notifications' => '0',
            'api_rate_limit' => '500',
        ];

        $response = $this->post('/settings/import', [
            'settings_file' => json_encode($importData),
        ]);

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Settings imported successfully');

        foreach ($importData as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    public function test_settings_import_validates_data()
    {
        $invalidImportData = [
            'app_name' => '', // Invalid
            'timezone' => 'Invalid/Zone', // Invalid
            'api_rate_limit' => 'not-a-number', // Invalid
        ];

        $response = $this->post('/settings/import', [
            'settings_file' => json_encode($invalidImportData),
        ]);

        $response->assertRedirect('/settings')
            ->assertSessionHas('error', 'Import failed: Invalid settings data');
    }

    public function test_maintenance_mode_can_be_toggled()
    {
        $response = $this->post('/settings/maintenance/enable');

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Maintenance mode enabled');

        $this->assertDatabaseHas('settings', [
            'key' => 'maintenance_mode',
            'value' => '1',
        ]);

        $response = $this->post('/settings/maintenance/disable');

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Maintenance mode disabled');

        $this->assertDatabaseHas('settings', [
            'key' => 'maintenance_mode',
            'value' => '0',
        ]);
    }

    public function test_cache_can_be_cleared()
    {
        $response = $this->post('/settings/cache/clear');

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Cache cleared successfully');
    }

    public function test_logs_can_be_cleared()
    {
        $response = $this->post('/settings/logs/clear');

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Logs cleared successfully');
    }

    public function test_system_info_displays_correctly()
    {
        $response = $this->get('/settings/system-info');

        $response->assertStatus(200)
            ->assertViewIs('settings.system-info')
            ->assertSee('System Information')
            ->assertSee('PHP Version')
            ->assertSee('Laravel Version')
            ->assertSee('Database')
            ->assertSee('Server Information');
    }

    public function test_backup_settings_works()
    {
        Settings::create(['key' => 'app_name', 'value' => 'Test App']);
        Settings::create(['key' => 'timezone', 'value' => 'UTC']);

        $response = $this->post('/settings/backup');

        $response->assertRedirect('/settings')
            ->assertSessionHas('success', 'Settings backup created successfully');

        // Verify backup file was created (this would depend on implementation)
        // In a real test, you might check if a backup file exists in storage
    }

    public function test_restore_settings_works()
    {
        // This test would require creating a backup first
        // For now, we'll test the endpoint exists and handles the request
        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/dashboard/settings/restore', [
            '_token' => 'test-token',
            'backup_file' => 'non-existent-backup.json',
        ]);

        $response->assertRedirect('/dashboard/settings')
            ->assertSessionHas('error'); // Should fail with non-existent file
    }

    public function test_webhook_test_configuration_works()
    {
        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/dashboard/settings/test-webhook', [
            '_token' => 'test-token',
            'test_url' => 'https://httpbin.org/post',
            'test_payload' => json_encode(['test' => 'data']),
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'response_time',
                'status_code',
            ]);
    }

    public function test_unauthenticated_users_cannot_access_settings()
    {
        $this->post('/logout');

        $response = $this->get('/dashboard/settings');
        $response->assertRedirect('/login');

        $response = $this->put('/dashboard/settings/general', ['app_name' => 'Test']);
        $response->assertRedirect('/login');

        $response = $this->post('/dashboard/settings/reset');
        $response->assertRedirect('/login');
    }

    public function test_settings_helper_functions_work()
    {
        Settings::create([
            'slack_webhook_url' => 'https://hooks.slack.com/test',
            'slack_notifications_enabled' => true,
            'notification_email' => 'test@example.com',
            'email_notifications_enabled' => true,
        ]);

        $settings = Settings::first();
        $this->assertEquals('https://hooks.slack.com/test', $settings->slack_webhook_url);
        $this->assertTrue($settings->slack_notifications_enabled);
        $this->assertEquals('test@example.com', $settings->notification_email);
        $this->assertTrue($settings->email_notifications_enabled);
    }

    public function test_settings_are_cached_properly()
    {
        // Create a setting
        $settings = Settings::create([
            'slack_webhook_url' => 'https://hooks.slack.com/original',
            'slack_notifications_enabled' => true,
            'notification_email' => 'original@example.com',
            'email_notifications_enabled' => false,
        ]);

        // First access should cache the value
        $cachedSettings = cache()->remember('app_settings', 3600, function () {
            return Settings::first();
        });
        $this->assertEquals('https://hooks.slack.com/original', $cachedSettings->slack_webhook_url);

        // Update the setting directly in database (bypassing cache)
        $settings->update(['slack_webhook_url' => 'https://hooks.slack.com/updated']);

        // Should still return cached value
        $cachedSettings2 = cache()->get('app_settings');
        $this->assertEquals('https://hooks.slack.com/original', $cachedSettings2->slack_webhook_url);

        // Clear cache and check again
        cache()->forget('app_settings');
        $freshSettings = Settings::first();
        $this->assertEquals('https://hooks.slack.com/updated', $freshSettings->slack_webhook_url);
    }
}