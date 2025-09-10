<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $fillable = [
        'slack_webhook_url',
        'slack_notifications_enabled',
        'notification_email',
        'email_notifications_enabled',
    ];

    protected $casts = [
        'slack_notifications_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
    ];

    /**
     * Get the current settings instance (singleton pattern)
     */
    public static function current()
    {
        return static::first() ?? static::create([]);
    }
}
