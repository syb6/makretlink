<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders_with_expiry_notice(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk()
            ->assertSee('Forgot your password?')
            ->assertSee('30 minutes');
    }

    public function test_login_page_offers_forgot_password_link(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Forgot password?');
    }

    public function test_valid_token_resets_password_and_signs_the_user_in(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com', 'password' => bcrypt('old-password')]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $response->assertRedirect();
        $this->assertTrue(auth()->check());

        // Fresh credentials work; old ones no longer do.
        $this->assertTrue(password_verify('new-secret-123', $user->fresh()->password));
        $this->assertFalse(password_verify('old-password', $user->fresh()->password));
    }

    public function test_garbage_token_is_rejected_with_helpful_error(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('password.reset', ['token' => 'nope']))
            ->post(route('password.update'), [
                'token' => 'nope',
                'email' => $user->email,
                'password' => 'another-secret-1',
                'password_confirmation' => 'another-secret-1',
            ]);

        $response->assertRedirect(route('password.reset', ['token' => 'nope']));
        $response->assertSessionHasErrors('email');
        $this->assertFalse(auth()->check());
        $this->assertTrue(password_verify('password', $user->fresh()->password)); // unchanged (factory default)
    }
}
