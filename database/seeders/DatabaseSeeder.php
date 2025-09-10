<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a sample project
        $project = Project::create([
            'name' => 'Demo Project',
            'description' => 'A demo project for testing webhook functionality',
            'is_active' => true,
            'settings' => [
                'max_events_per_hour' => 1000,
                'retention_days' => 30,
            ],
        ]);

        // Create sample webhook endpoints
        WebhookEndpoint::create([
            'project_id' => $project->id,
            'name' => 'Payment Events',
            'destination_urls' => [
                'https://httpbin.org/post',
                'https://webhook.site/unique-id',
            ],
            'auth_method' => 'hmac',
            'is_active' => true,
            'retry_config' => [
                'max_attempts' => 5,
                'backoff_strategy' => 'exponential',
                'initial_delay' => 30,
            ],
            'headers_config' => [
                'X-Source' => 'Hookbytes-Gateway',
            ],
        ]);

        WebhookEndpoint::create([
            'project_id' => $project->id,
            'name' => 'User Events',
            'destination_urls' => [
                'https://httpbin.org/post',
            ],
            'auth_method' => 'shared_secret',
            'is_active' => true,
            'retry_config' => [
                'max_attempts' => 3,
                'backoff_strategy' => 'linear',
                'initial_delay' => 60,
            ],
        ]);

        $this->command->info('Sample data created successfully!');
        $this->command->info('Project: ' . $project->name . ' (slug: ' . $project->slug . ')');
        $this->command->info('API Key: ' . $project->api_key);
        $this->command->info('Webhook Secret: ' . $project->webhook_secret);
    }
}
