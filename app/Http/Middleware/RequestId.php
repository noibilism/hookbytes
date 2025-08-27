<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or use existing request ID
        $requestId = $request->header('X-Request-ID') ?? $this->generateRequestId();
        
        // Add request ID to the request for use in the application
        $request->headers->set('X-Request-ID', $requestId);
        
        // Add request ID to the logging context
        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Process the request
        $response = $next($request);
        
        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);
        
        // Add CORS headers if needed
        if ($request->isMethod('OPTIONS')) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Request-ID');
            $response->headers->set('Access-Control-Expose-Headers', 'X-Request-ID');
        }
        
        return $response;
    }
    
    /**
     * Generate a unique request ID
     *
     * @return string
     */
    private function generateRequestId(): string
    {
        return 'req_' . Str::random(16) . '_' . time();
    }
}
