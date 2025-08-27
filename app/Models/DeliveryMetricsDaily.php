<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryMetricsDaily extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'tenant_id',
        'subscription_id',
        'event_type',
        'sent_count',
        'success_count',
        'fail_count',
        'avg_latency_ms',
    ];

    protected $casts = [
        'date' => 'date',
        'sent_count' => 'integer',
        'success_count' => 'integer',
        'fail_count' => 'integer',
        'avg_latency_ms' => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
