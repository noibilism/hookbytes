<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Delivery extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'event_id',
        'subscription_id',
        'attempt',
        'status',
        'response_code',
        'response_body',
        'error_message',
        'duration_ms',
        'next_retry_at',
        'signature',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'response_code' => 'integer',
        'duration_ms' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function deadLetter(): HasOne
    {
        return $this->hasOne(DeadLetter::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
                    ->whereNotNull('next_retry_at')
                    ->where('next_retry_at', '<=', now());
    }

    public function scopeForSubscription($query, string $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
    }

    public function scopeForEvent($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
