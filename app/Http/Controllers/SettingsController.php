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
        return view('dashboard.settings.index', compact('settings'));
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

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully!');
    }
}
