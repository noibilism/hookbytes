<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Subscription extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'endpoint_url',
        'secret',
        'event_types',
        'active',
        'rate_limit_per_minute',
        'max_retries',
        'headers',
        'signature_algo',
    ];

    protected $casts = [
        'event_types' => 'array',
        'headers' => 'array',
        'active' => 'boolean',
        'rate_limit_per_minute' => 'integer',
        'max_retries' => 'integer',
    ];

    protected $hidden = [
        'secret',
    ];

    public function getSecretAttribute($value): string
    {
        return $value ? Crypt::decryptString($value) : '';
    }

    public function setSecretAttribute($value): void
    {
        $this->attributes['secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function deliveryMetrics(): HasMany
    {
        return $this->hasMany(DeliveryMetricsDaily::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForEventType($query, string $eventType)
    {
        return $query->whereJsonContains('event_types', $eventType);
    }
}
