<?php

namespace App\Console\Commands;

use App\Models\WebhookEndpoint;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateShortUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:generate-short-urls {--force : Force regeneration of existing short URLs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate short URLs for webhook endpoints that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $query = WebhookEndpoint::query();
        
        if (!$force) {
            $query->whereNull('short_url');
        }
        
        $endpoints = $query->get();
        
        $this->info('Generating short URLs for webhook endpoints...');
        
        if ($endpoints->isEmpty()) {
            $this->info('Generated short URLs for 0 webhook endpoints.');
            return 0;
        }
        
        $progressBar = $this->output->createProgressBar($endpoints->count());
        $progressBar->start();
        
        foreach ($endpoints as $endpoint) {
            $endpoint->short_url = $this->generateUniqueShortUrl();
            $endpoint->save();
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info("Generated short URLs for {$endpoints->count()} webhook endpoints.");
        
        return 0;
    }
    
    /**
     * Generate a unique short URL for the webhook endpoint
     */
    private function generateUniqueShortUrl(): string
    {
        do {
            $shortUrl = Str::random(8);
        } while (WebhookEndpoint::where('short_url', $shortUrl)->exists());
        
        return $shortUrl;
    }
}
