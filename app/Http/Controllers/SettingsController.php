<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        $settings = Settings::current();
        return view('dashboard.settings', compact('settings'));
    }

    /**
     * Update general settings
     */
    public function updateGeneral(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_description' => 'nullable|string|max:500',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'time_format' => 'required|string',
        ]);

        $settings = Settings::current();
        $settings->update([
            'app_name' => $request->app_name,
            'app_description' => $request->app_description,
            'timezone' => $request->timezone,
            'date_format' => $request->date_format,
            'time_format' => $request->time_format,
        ]);

        return redirect()->route('dashboard.settings')
            ->with('success', 'General settings updated successfully');
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'slack_webhook_url' => 'nullable|url',
            'slack_notifications_enabled' => 'boolean',
            'notification_email' => 'nullable|email',
            'email_notifications_enabled' => 'boolean',
        ]);

        $settings = Settings::current();
        $settings->update([
            'slack_webhook_url' => $request->slack_webhook_url,
            'slack_notifications_enabled' => $request->has('slack_notifications_enabled'),
            'notification_email' => $request->notification_email,
            'email_notifications_enabled' => $request->has('email_notifications_enabled'),
        ]);

        return redirect()->route('dashboard.settings')
            ->with('success', 'Notification settings updated successfully');
    }

    /**
     * Update security settings
     */
    public function updateSecurity(Request $request)
    {
        $request->validate([
            'api_rate_limit' => 'required|integer|min:1|max:10000',
            'webhook_timeout' => 'required|integer|min:1|max:300',
            'max_retry_attempts' => 'required|integer|min:0|max:10',
            'require_webhook_signature' => 'boolean',
        ]);

        $settings = Settings::current();
        $settings->update([
            'api_rate_limit' => $request->api_rate_limit,
            'webhook_timeout' => $request->webhook_timeout,
            'max_retry_attempts' => $request->max_retry_attempts,
            'require_webhook_signature' => $request->has('require_webhook_signature'),
        ]);

        return redirect()->route('dashboard.settings')
            ->with('success', 'Security settings updated successfully');
    }

    /**
     * Update the notification settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'slack_webhook_url' => 'nullable|url',
            'slack_notifications_enabled' => 'boolean',
            'notification_email' => 'nullable|email',
            'email_notifications_enabled' => 'boolean',
        ]);

        $settings = Settings::current();
        $settings->update([
            'slack_webhook_url' => $request->slack_webhook_url,
            'slack_notifications_enabled' => $request->has('slack_notifications_enabled'),
            'notification_email' => $request->notification_email,
            'email_notifications_enabled' => $request->has('email_notifications_enabled'),
        ]);

        return redirect()->route('dashboard.settings')
            ->with('success', 'Settings updated successfully!');
    }
}
