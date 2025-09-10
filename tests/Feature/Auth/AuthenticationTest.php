<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestsWithAuthentication;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, TestsWithAuthentication;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['_token' => 'test-token'])
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                '_token' => 'test-token'
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->withSession(['_token' => 'test-token'])
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                '_token' => 'test-token'
            ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/logout', ['_token' => 'test-token']);

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
