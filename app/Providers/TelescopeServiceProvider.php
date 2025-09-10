<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag() ||
                   $this->isWebhookRelated($entry);
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters([
            '_token',
            'api_key',
            'webhook_secret',
            'encryption_key',
            'password',
        ]);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
            'x-api-key',
            'x-webhook-signature',
        ]);
    }

    /**
     * Check if the entry is related to webhook operations.
     */
    protected function isWebhookRelated(IncomingEntry $entry): bool
    {
        if ($entry->type === 'request') {
            $uri = $entry->content['uri'] ?? '';
            return str_contains($uri, '/webhook') || 
                   str_contains($uri, '/api/events') ||
                   str_contains($uri, '/api/projects');
        }

        if ($entry->type === 'job') {
            $job = $entry->content['name'] ?? '';
            return str_contains($job, 'Webhook') || 
                   str_contains($job, 'Event') ||
                   str_contains($job, 'Delivery');
        }

        return false;
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
