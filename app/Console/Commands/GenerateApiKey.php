<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apikey:generate {--user-id= : The ID of the user} {--email= : The email of the user} {--regenerate : Regenerate existing API key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate or regenerate API key for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $email = $this->option('email');
        $regenerate = $this->option('regenerate');

        // Find user by ID or email
        if ($userId) {
            $user = User::find($userId);
        } elseif ($email) {
            $user = User::where('email', $email)->first();
        } else {
            $this->error('Please provide either --user-id or --email option.');
            return 1;
        }

        if (!$user) {
            $this->error('User not found.');
            return 1;
        }

        // Check if user already has an API key
        if ($user->hasApiKey() && !$regenerate) {
            $this->warn("User {$user->email} already has an API key.");
            $this->info('Use --regenerate flag to generate a new one.');
            return 1;
        }

        // Generate or regenerate API key
        if ($regenerate && $user->hasApiKey()) {
            $apiKey = $user->regenerateApiKey();
            $this->info("API key regenerated for user: {$user->email}");
        } else {
            $apiKey = $user->generateApiKey();
            $this->info("API key generated for user: {$user->email}");
        }

        $this->line('');
        $this->line('API Key: ' . $apiKey);
        $this->line('');
        $this->warn('Please store this API key securely. It will not be shown again.');
        
        return 0;
    }
}
