<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ThrottleService
{
    /**
     * Cache key prefix for rate limiting
     */
    const CACHE_PREFIX = 'throttle:';

    /**
     * Default rate limit window in seconds (1 minute)
     */
    const DEFAULT_WINDOW = 60;

    /**
     * Check if a subscription is throttled
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function isThrottled(Subscription $subscription): bool
    {
        if (!$subscription->rate_limit_per_minute) {
            return false;
        }

        $key = $this->getThrottleKey($subscription->id);
        $currentCount = $this->getCurrentCount($key);
        
        return $currentCount >= $subscription->rate_limit_per_minute;
    }

    /**
     * Increment the throttle counter for a subscription
     *
     * @param Subscription $subscription
     * @return int Current count after increment
     */
    public function increment(Subscription $subscription): int
    {
        $key = $this->getThrottleKey($subscription->id);
        
        // Use Redis for atomic operations if available
        if ($this->isRedisAvailable()) {
            return $this->incrementWithRedis($key);
        }
        
        return $this->incrementWithCache($key);
    }

    /**
     * Get current throttle count for a subscription
     *
     * @param string $subscriptionId
     * @return int
     */
    public function getCurrentCount(string $subscriptionId): int
    {
        $key = $this->getThrottleKey($subscriptionId);
        
        return (int) Cache::get($key, 0);
    }

    /**
     * Get remaining requests for a subscription
     *
     * @param Subscription $subscription
     * @return int
     */
    public function getRemainingRequests(Subscription $subscription): int
    {
        if (!$subscription->rate_limit_per_minute) {
            return PHP_INT_MAX;
        }
        
        $currentCount = $this->getCurrentCount($subscription->id);
        
        return max(0, $subscription->rate_limit_per_minute - $currentCount);
    }

    /**
     * Get time until throttle resets
     *
     * @param string $subscriptionId
     * @return int Seconds until reset
     */
    public function getTimeUntilReset(string $subscriptionId): int
    {
        $key = $this->getThrottleKey($subscriptionId);
        
        if ($this->isRedisAvailable()) {
            $ttl = Redis::ttl($key);
            return $ttl > 0 ? $ttl : 0;
        }
        
        // For cache-based implementation, we can't get exact TTL
        // Return the window size as approximation
        return Cache::has($key) ? self::DEFAULT_WINDOW : 0;
    }

    /**
     * Reset throttle counter for a subscription
     *
     * @param string $subscriptionId
     * @return void
     */
    public function reset(string $subscriptionId): void
    {
        $key = $this->getThrottleKey($subscriptionId);
        
        if ($this->isRedisAvailable()) {
            Redis::del($key);
        } else {
            Cache::forget($key);
        }
    }

    /**
     * Check if delivery should be delayed due to throttling
     *
     * @param Subscription $subscription
     * @return array [should_delay, delay_seconds]
     */
    public function shouldDelay(Subscription $subscription): array
    {
        if ($this->isThrottled($subscription)) {
            $delaySeconds = $this->getTimeUntilReset($subscription->id);
            return [true, $delaySeconds];
        }
        
        return [false, 0];
    }

    /**
     * Get throttle statistics for a subscription
     *
     * @param Subscription $subscription
     * @return array
     */
    public function getStats(Subscription $subscription): array
    {
        $currentCount = $this->getCurrentCount($subscription->id);
        $remaining = $this->getRemainingRequests($subscription);
        $resetTime = $this->getTimeUntilReset($subscription->id);
        
        return [
            'limit' => $subscription->rate_limit_per_minute ?? 0,
            'current' => $currentCount,
            'remaining' => $remaining,
            'reset_in_seconds' => $resetTime,
            'is_throttled' => $this->isThrottled($subscription)
        ];
    }

    /**
     * Get throttle key for a subscription
     *
     * @param string $subscriptionId
     * @return string
     */
    private function getThrottleKey(string $subscriptionId): string
    {
        $window = floor(time() / self::DEFAULT_WINDOW);
        return self::CACHE_PREFIX . $subscriptionId . ':' . $window;
    }

    /**
     * Increment counter using Redis
     *
     * @param string $key
     * @return int
     */
    private function incrementWithRedis(string $key): int
    {
        $count = Redis::incr($key);
        
        // Set expiration on first increment
        if ($count === 1) {
            Redis::expire($key, self::DEFAULT_WINDOW);
        }
        
        return $count;
    }

    /**
     * Increment counter using Cache
     *
     * @param string $key
     * @return int
     */
    private function incrementWithCache(string $key): int
    {
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, self::DEFAULT_WINDOW);
        
        return $count;
    }

    /**
     * Check if Redis is available
     *
     * @return bool
     */
    private function isRedisAvailable(): bool
    {
        try {
            return config('database.redis.default') && Redis::ping();
        } catch (\Exception $e) {
            return false;
        }
    }
}