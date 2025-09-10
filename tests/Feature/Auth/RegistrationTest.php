<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestsWithAuthentication;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, TestsWithAuthentication;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->withSession(['_token' => 'test-token'])
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                '_token' => 'test-token'
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
