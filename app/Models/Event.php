<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_id',
        'project_id',
        'webhook_endpoint_id',
        'event_type',
        'payload',
        'headers',
        'source_ip',
        'user_agent',
        'status',
        'delivery_attempts',
        'last_attempt_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'delivery_attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(EventDelivery::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->event_id)) {
                $event->event_id = (string) Str::uuid();
            }
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }
}
