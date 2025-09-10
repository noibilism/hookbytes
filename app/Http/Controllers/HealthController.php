<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    /**
     * Comprehensive health check endpoint
     */
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        // Add optional checks that might not be available
        try {
            if (extension_loaded('redis') && config('database.redis.default.host')) {
                $checks['redis'] = $this->checkRedis();
            }
        } catch (Exception $e) {
            $checks['redis'] = [
                'status' => 'skipped',
                'message' => 'Redis not available: ' . $e->getMessage()
            ];
        }
        
        try {
            if (config('queue.default') !== 'sync') {
                $checks['queue'] = $this->checkQueue();
            }
        } catch (Exception $e) {
            $checks['queue'] = [
                'status' => 'skipped',
                'message' => 'Queue not available: ' . $e->getMessage()
            ];
        }

        $overallStatus = collect($checks)->every(fn($check) => in_array($check['status'], ['ok', 'warning', 'skipped'])) ? 'ok' : 'error';
        $httpStatus = $overallStatus === 'ok' ? 200 : 503;

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'service' => 'HookBytes Webhook Gateway',
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
        ], $httpStatus);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'response_time_ms' => $duration,
                'connection' => config('database.default'),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): array
    {
        try {
            // Check if Redis is configured
            if (!config('database.redis.default.host')) {
                return [
                    'status' => 'skipped',
                    'message' => 'Redis not configured',
                ];
            }

            // Check if Redis extension is available
            if (!extension_loaded('redis') && !class_exists('Predis\Client')) {
                return [
                    'status' => 'warning',
                    'message' => 'Redis extension not installed',
                ];
            }

            $start = microtime(true);
            Redis::ping();
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'message' => 'Redis connection successful',
                'response_time_ms' => $duration,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache functionality working',
                    'response_time_ms' => $duration,
                    'driver' => config('cache.default'),
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Cache read/write test failed',
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache functionality failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connectivity
     */
    private function checkQueue(): array
    {
        try {
            $start = microtime(true);
            $queueSize = Queue::size();
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'message' => 'Queue connection successful',
                'response_time_ms' => $duration,
                'driver' => config('queue.default'),
                'pending_jobs' => $queueSize,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility
     */
    private function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health check test';
            
            Storage::put($testFile, $testContent);
            $retrieved = Storage::get($testFile);
            Storage::delete($testFile);
            
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testContent) {
                return [
                    'status' => 'ok',
                    'message' => 'Storage functionality working',
                    'response_time_ms' => $duration,
                    'driver' => config('filesystems.default'),
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Storage read/write test failed',
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage functionality failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}