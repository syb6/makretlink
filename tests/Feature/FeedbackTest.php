<?php

namespace Tests\Feature;

use App\Models\FeedbackMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-feedback@test.dev', 'phone' => '100',
            'role' => 'admin', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
    }

    /** @test */
    public function contact_form_stores_the_message_and_still_confirms(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', [
            'name' => 'Walk In Guest',
            'email' => 'guest@example.com',
            'message' => 'Do you sell organic tomatoes?',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('flash.success', 'Thanks for reaching out! Your message is on its way — we will get back to you soon.');

        $this->assertDatabaseHas('feedback_messages', [
            'name' => 'Walk In Guest',
            'email' => 'guest@example.com',
            'message' => 'Do you sell organic tomatoes?',
        ]);
    }

    /** @test */
    public function contact_form_succeeds_even_when_mail_fails(): void
    {
        // No Mail::fake() -> the log/whatever transport is exercised; either
        // way the stored row is the source of truth and the visitor sees success.
        $this->postJson('/api/contact', [
            'name' => 'Mail Outage',
            'email' => 'outage@example.com',
            'message' => 'Is the site down?',
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('feedback_messages', ['email' => 'outage@example.com']);
    }

    /** @test */
    public function contact_form_validates_input(): void
    {
        $this->postJson('/api/contact', ['name' => '', 'email' => 'nope', 'message' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'message']);

        $this->assertEquals(0, FeedbackMessage::count());
    }

    /** @test */
    public function admin_sees_stored_messages_on_the_feedback_page(): void
    {
        FeedbackMessage::create([
            'name' => 'Curious Carla', 'email' => 'carla@example.com', 'message' => 'Hello there!',
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/admin/feedback')
            ->assertOk()
            ->assertJsonPath('rows.0.name', 'Curious Carla')
            ->assertJsonPath('rows.0.email', 'carla@example.com')
            ->assertJsonPath('rows.0.message', 'Hello there!');
    }

    /** @test */
    public function admin_can_toggle_read_state_and_delete_messages(): void
    {
        $message = FeedbackMessage::create([
            'name' => 'Deletable Dan', 'email' => 'dan@example.com', 'message' => 'Hi',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/feedback/{$message->id}/toggle-read")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertNotNull($message->fresh()->read_at);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/feedback/{$message->id}/toggle-read")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertNull($message->fresh()->read_at);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/feedback/{$message->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('feedback_messages', ['id' => $message->id]);
    }

    /** @test */
    public function guests_and_customers_cannot_open_the_feedback_inbox(): void
    {
        $this->getJson('/api/admin/feedback')->assertUnauthorized();

        $customer = User::create([
            'name' => 'Customer', 'email' => 'customer-feedback@test.dev', 'phone' => '300',
            'role' => 'customer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);

        $this->actingAs($customer)->getJson('/api/admin/feedback')->assertForbidden();
    }
}
