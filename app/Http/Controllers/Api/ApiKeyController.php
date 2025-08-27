<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiKeyController extends Controller
{
    /**
     * Get current user's API key information (without revealing the key).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'has_api_key' => $user->hasApiKey(),
            'api_key_created_at' => $user->api_key_created_at,
            'api_key_last_used_at' => $user->api_key_last_used_at,
        ]);
    }

    /**
     * Generate a new API key for the current user.
     */
    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->hasApiKey()) {
            return response()->json([
                'error' => 'API key already exists',
                'message' => 'User already has an API key. Use regenerate endpoint to create a new one.'
            ], 409);
        }
        
        $apiKey = $user->generateApiKey();
        
        return response()->json([
            'message' => 'API key generated successfully',
            'api_key' => $apiKey,
            'warning' => 'Please store this API key securely. It will not be shown again.'
        ], 201);
    }

    /**
     * Regenerate API key for the current user.
     */
    public function regenerate(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $apiKey = $user->regenerateApiKey();
        
        return response()->json([
            'message' => 'API key regenerated successfully',
            'api_key' => $apiKey,
            'warning' => 'Please store this API key securely. It will not be shown again.'
        ]);
    }

    /**
     * Revoke (delete) the current user's API key.
     */
    public function revoke(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasApiKey()) {
            return response()->json([
                'error' => 'No API key found',
                'message' => 'User does not have an API key to revoke.'
            ], 404);
        }
        
        $user->api_key = null;
        $user->api_key_created_at = null;
        $user->api_key_last_used_at = null;
        $user->save();
        
        return response()->json([
            'message' => 'API key revoked successfully'
        ]);
    }
}
