<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventDelivery extends Model
{
    protected $fillable = [
        'event_id',
        'destination_url',
        'attempt_number',
        'status',
        'response_code',
        'response_body',
        'response_headers',
        'latency_ms',
        'error_message',
        'attempted_at',
    ];

    protected $casts = [
        'response_headers' => 'array',
        'attempt_number' => 'integer',
        'response_code' => 'integer',
        'latency_ms' => 'integer',
        'attempted_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeTimeout($query)
    {
        return $query->where('status', 'timeout');
    }
}
