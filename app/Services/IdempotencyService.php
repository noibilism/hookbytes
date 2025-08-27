<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IdempotencyService
{
    /**
     * Default TTL for idempotency keys (24 hours)
     */
    const DEFAULT_TTL = 86400;

    /**
     * Cache key prefix for idempotency
     */
    const CACHE_PREFIX = 'idempotency:';

    /**
     * Check if an idempotency key exists
     *
     * @param string $idempotencyKey
     * @param string $tenantId
     * @return bool
     */
    public function exists(string $idempotencyKey, string $tenantId): bool
    {
        $cacheKey = $this->getCacheKey($idempotencyKey, $tenantId);
        
        // Check cache first for performance
        if (Cache::has($cacheKey)) {
            return true;
        }
        
        // Check database as fallback
        return Event::byIdempotencyKey($idempotencyKey)
            ->forTenant($tenantId)
            ->exists();
    }

    /**
     * Get existing event by idempotency key
     *
     * @param string $idempotencyKey
     * @param string $tenantId
     * @return Event|null
     */
    public function getExistingEvent(string $idempotencyKey, string $tenantId): ?Event
    {
        $cacheKey = $this->getCacheKey($idempotencyKey, $tenantId);
        
        // Try to get event ID from cache
        $eventId = Cache::get($cacheKey);
        
        if ($eventId) {
            return Event::find($eventId);
        }
        
        // Fallback to database query
        $event = Event::byIdempotencyKey($idempotencyKey)
            ->forTenant($tenantId)
            ->first();
            
        if ($event) {
            // Cache the result for future lookups
            $this->store($idempotencyKey, $tenantId, $event->id);
        }
        
        return $event;
    }

    /**
     * Store idempotency key with event ID
     *
     * @param string $idempotencyKey
     * @param string $tenantId
     * @param string $eventId
     * @param int $ttl
     * @return void
     */
    public function store(string $idempotencyKey, string $tenantId, string $eventId, int $ttl = self::DEFAULT_TTL): void
    {
        $cacheKey = $this->getCacheKey($idempotencyKey, $tenantId);
        
        Cache::put($cacheKey, $eventId, $ttl);
    }

    /**
     * Generate a unique idempotency key
     *
     * @return string
     */
    public function generateKey(): string
    {
        return Str::ulid()->toBase32();
    }

    /**
     * Validate idempotency key format
     *
     * @param string $idempotencyKey
     * @return bool
     */
    public function isValidKey(string $idempotencyKey): bool
    {
        // Must be between 1 and 255 characters
        if (strlen($idempotencyKey) < 1 || strlen($idempotencyKey) > 255) {
            return false;
        }
        
        // Must contain only alphanumeric characters, hyphens, and underscores
        return preg_match('/^[a-zA-Z0-9_-]+$/', $idempotencyKey) === 1;
    }

    /**
     * Remove idempotency key from cache
     *
     * @param string $idempotencyKey
     * @param string $tenantId
     * @return void
     */
    public function forget(string $idempotencyKey, string $tenantId): void
    {
        $cacheKey = $this->getCacheKey($idempotencyKey, $tenantId);
        
        Cache::forget($cacheKey);
    }

    /**
     * Get cache key for idempotency
     *
     * @param string $idempotencyKey
     * @param string $tenantId
     * @return string
     */
    private function getCacheKey(string $idempotencyKey, string $tenantId): string
    {
        return self::CACHE_PREFIX . $tenantId . ':' . $idempotencyKey;
    }

    /**
     * Clean up expired idempotency keys from cache
     *
     * @return void
     */
    public function cleanup(): void
    {
        // This would typically be handled by cache TTL,
        // but can be implemented for manual cleanup if needed
        // Implementation depends on cache driver capabilities
    }
}