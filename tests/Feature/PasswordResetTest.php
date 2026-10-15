<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_api_sends_a_link_without_exposing_user_existence(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->postJson('/api/password/email', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('flash.reset-status', __(Password::RESET_LINK_SENT));

        Mail::assertSent(\App\Mail\PasswordResetMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_api_auth_bootstrap_returns_a_guest_state(): void
    {
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user', null);
    }

    public function test_valid_token_resets_password_and_signs_the_user_in(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com', 'password' => bcrypt('old-password')]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/password/reset', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertTrue(auth()->check());

        // Fresh credentials work; old ones no longer do.
        $this->assertTrue(password_verify('new-secret-123', $user->fresh()->password));
        $this->assertFalse(password_verify('old-password', $user->fresh()->password));
    }

    public function test_garbage_token_is_rejected_with_helpful_error(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/password/reset', [
                'token' => 'nope',
                'email' => $user->email,
                'password' => 'another-secret-1',
                'password_confirmation' => 'another-secret-1',
            ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['validation' => ['email']])
            ->assertJsonPath('status', 422);
        $this->assertFalse(auth()->check());
        $this->assertTrue(password_verify('password', $user->fresh()->password)); // unchanged (factory default)
    }
}
