<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'api_key',
        'webhook_secret',
        'is_active',
        'settings',
        'allowed_ips',
        'rate_limits',
        'permissions',
        'require_https',
        'encrypt_payloads',
        'encryption_key',
        'last_activity_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'allowed_ips' => 'array',
        'rate_limits' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'require_https' => 'boolean',
        'encrypt_payloads' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'webhook_secret',
        'encryption_key',
    ];

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the API key (normally hidden)
     */
    public function getApiKeyAttribute($value)
    {
        return $value;
    }

    /**
     * Get the webhook secret (normally hidden)
     */
    public function getWebhookSecretAttribute($value)
    {
        return $value;
    }

    /**
     * Make sensitive fields visible for API configuration display
     */
    public function makeVisible($attributes)
    {
        $this->hidden = array_diff($this->hidden, (array) $attributes);
        return $this;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
            if (empty($project->api_key)) {
                $project->api_key = 'hwg_' . Str::random(32);
            }
            if (empty($project->webhook_secret)) {
                $project->webhook_secret = Str::random(64);
            }
        });
    }
}
