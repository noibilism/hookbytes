<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebhookEndpoint extends Model
{
    use HasFactory;
    protected $fillable = [
        'project_id',
        'name',
        'slug',
        'url_path',
        'short_url',
        'destination_urls',
        'auth_method',
        'auth_secret',
        'is_active',
        'retry_config',
        'headers_config',
    ];

    protected $casts = [
        'destination_urls' => 'array',
        'retry_config' => 'array',
        'headers_config' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'auth_secret',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function transformations(): HasMany
    {
        return $this->hasMany(WebhookTransformation::class);
    }

    public function activeTransformations(): HasMany
    {
        return $this->transformations()->active()->orderedByPriority();
    }

    public function routingRules(): HasMany
    {
        return $this->hasMany(WebhookRoutingRule::class);
    }

    public function activeRoutingRules(): HasMany
    {
        return $this->routingRules()->active()->orderedByPriority();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($endpoint) {
            if (empty($endpoint->slug)) {
                $endpoint->slug = Str::slug($endpoint->name);
            }
            if (empty($endpoint->url_path)) {
                $project = Project::find($endpoint->project_id);
                $endpoint->url_path = $project->slug . '/' . $endpoint->slug;
            }
            if (empty($endpoint->auth_secret) && $endpoint->auth_method !== 'none') {
                $endpoint->auth_secret = Str::random(32);
            }
            
            // Generate unique short URL
             if (empty($endpoint->short_url)) {
                 $endpoint->short_url = static::generateUniqueShortUrl();
             }
         });
    }

    /**
     * Generate a unique short URL for the webhook endpoint
     */
    private static function generateUniqueShortUrl(): string
    {
        do {
            $shortUrl = Str::random(8);
        } while (static::where('short_url', $shortUrl)->exists());
        
        return $shortUrl;
    }
}
