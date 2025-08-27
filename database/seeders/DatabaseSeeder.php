<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for dashboard login
        \App\Models\User::factory()->withApiKey()->create([
            'name' => 'Admin User',
            'email' => 'admin@webhook.local',
            'password' => 'password', // Will be hashed by the factory
        ]);

        // Create a test user with API key
        \App\Models\User::factory()->withApiKey()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create additional test users
        \App\Models\User::factory(5)->withApiKey()->create();
    }
}
