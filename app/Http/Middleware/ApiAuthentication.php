<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $role
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        // Check for API key in header or query parameter
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        
        if (!$apiKey) {
            return $this->unauthorizedResponse('API key is required');
        }

        // Validate API key format
        if (!$this->isValidApiKeyFormat($apiKey)) {
            return $this->unauthorizedResponse('Invalid API key format');
        }

        // Find project by API key
        $project = Project::where('api_key', $apiKey)->first();
        
        if (!$project) {
            return $this->unauthorizedResponse('Invalid API key');
        }

        // Check if project is active
        if (!$project->is_active) {
            return $this->unauthorizedResponse('Project is inactive');
        }

        // Rate limiting check
        if ($this->isRateLimited($project, $request)) {
            return $this->rateLimitResponse();
        }

        // Role-based access control
        if ($role && !$this->hasPermission($project, $role, $request)) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        // Add project to request for use in controllers
        $request->attributes->set('project', $project);
        
        // Log API access
        $this->logApiAccess($project, $request);
        
        return $next($request);
    }

    /**
     * Validate API key format
     */
    private function isValidApiKeyFormat(string $apiKey): bool
    {
        // API key should be at least 32 characters long and contain only alphanumeric characters
        return preg_match('/^[a-zA-Z0-9]{32,}$/', $apiKey);
    }

    /**
     * Check if request is rate limited
     */
    private function isRateLimited(Project $project, Request $request): bool
    {
        $cacheKey = "api_rate_limit:{$project->id}:" . $request->ip();
        $maxRequests = $project->rate_limit ?? 1000; // Default 1000 requests per hour
        $window = 3600; // 1 hour in seconds
        
        $currentCount = cache()->get($cacheKey, 0);
        
        if ($currentCount >= $maxRequests) {
            return true;
        }
        
        cache()->put($cacheKey, $currentCount + 1, $window);
        return false;
    }

    /**
     * Check if project has permission for the requested action
     */
    private function hasPermission(Project $project, string $role, Request $request): bool
    {
        $permissions = $project->permissions ?? [];
        
        // Define role-based permissions
        $rolePermissions = [
            'read' => ['events.list', 'events.show', 'endpoints.list'],
            'write' => ['events.create', 'events.replay'],
            'admin' => ['*'], // Full access
        ];
        
        if (!isset($rolePermissions[$role])) {
            return false;
        }
        
        $requiredPermissions = $rolePermissions[$role];
        
        // Admin role has full access
        if (in_array('*', $requiredPermissions)) {
            return true;
        }
        
        // Check if project has required permissions
        foreach ($requiredPermissions as $permission) {
            if (!in_array($permission, $permissions)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Log API access for monitoring
     */
    private function logApiAccess(Project $project, Request $request): void
    {
        Log::info('API Access', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
            'code' => 401,
        ], 401);
    }

    /**
     * Return rate limit response
     */
    private function rateLimitResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'Rate Limit Exceeded',
            'message' => 'Too many requests. Please try again later.',
            'code' => 429,
        ], 429);
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $message,
            'code' => 403,
        ], 403);
    }
}
