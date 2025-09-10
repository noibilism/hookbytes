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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($endpoint) {
            if (empty($endpoint->slug)) {
                $endpoint->slug = Str::slug($endpoint->name);
            }
            if (empty($endpoint->url_path)) {
                $endpoint->url_path = '/webhook/' . $endpoint->project->slug . '/' . $endpoint->slug;
            }
            if (empty($endpoint->auth_secret) && $endpoint->auth_method !== 'none') {
                $endpoint->auth_secret = Str::random(32);
            }
        });
    }
}
