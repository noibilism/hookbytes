<?php

namespace Tests;

trait TestsWithAuthentication
{
    protected function actingAsAuthenticatedUser($user = null)
    {
        if (!$user) {
            $user = \App\Models\User::factory()->create();
        }
        
        return $this->actingAs($user)->withSession([
            '_token' => 'test-token',
        ]);
    }
    
    protected function makeAuthenticatedRequest($method, $uri, $data = [], $user = null)
    {
        if (!$user) {
            $user = \App\Models\User::factory()->create();
        }
        
        return $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->$method($uri, array_merge($data, ['_token' => 'test-token']));
    }
}