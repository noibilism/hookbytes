<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class WebhookRoutingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'name',
        'description',
        'action',
        'priority',
        'is_active',
        'conditions',
        'destinations',
        'match_count',
        'last_matched_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'destinations' => 'array',
        'is_active' => 'boolean',
        'last_matched_at' => 'datetime',
        'match_count' => 'integer',
        'priority' => 'integer',
    ];

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeRouteRules(Builder $query): Builder
    {
        return $query->where('action', 'route');
    }

    public function scopeDropRules(Builder $query): Builder
    {
        return $query->where('action', 'drop');
    }

    public function incrementMatchCount(): void
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }

    public function isDropRule(): bool
    {
        return $this->action === 'drop';
    }

    public function isRouteRule(): bool
    {
        return $this->action === 'route';
    }
}
