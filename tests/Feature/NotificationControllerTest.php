<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\Concerns\CreatesMarketLinkData;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase, CreatesMarketLinkData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMarketLink();
    }

    /** Push a notification row straight into the table with a chosen payload. */
    private function notify(User $user, array $data = []): DatabaseNotification
    {
        $user->notify(new \App\Notifications\OrderStatusUpdate(
            $this->createOrder('placed'),
            $data['status'] ?? 'placed',
            null,
            $data['audience'] ?? 'customer',
        ));

        return $user->notifications()->first();
    }

    /** @test */
    public function mark_all_read_clears_every_unread_notification(): void
    {
        $this->actAsCustomer();
        $this->notify($this->customerUser, ['audience' => 'farmer']); // role mismatch is fine here
        $this->notify($this->customerUser, ['audience' => 'farmer']);

        $this->assertEquals(2, $this->customerUser->unreadNotifications()->count());

        $this->post(route('notifications.readAll'))->assertRedirect();

        $this->assertEquals(0, $this->customerUser->unreadNotifications()->count());
        $this->assertEquals(2, $this->customerUser->notifications()->whereNotNull('read_at')->count());
    }

    /** @test */
    public function mark_all_read_leaves_other_users_notifications_untouched(): void
    {
        $this->actAsCustomer();
        $this->notify($this->customerUser, ['audience' => 'farmer']);
        $farmerNotification = $this->notify($this->farmerUser, ['audience' => 'farmer']);

        $this->post(route('notifications.readAll'));

        $this->assertNull($farmerNotification->fresh()->read_at);
    }

    /** @test */
    public function mark_read_redirects_to_the_stored_deep_link(): void
    {
        $this->actAsCustomer();
        $notification = $this->notify($this->customerUser, ['audience' => 'customer']);

        $response = $this->post(route('notifications.read', $notification));

        $response->assertRedirect(route('orders.show', $notification->fresh()->data['order_id']));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function mark_read_falls_back_to_inbox_for_another_roles_deep_link(): void
    {
        // A customer holding a farmer-audience notification (farmer/* deep link).
        $this->actAsCustomer();
        $notification = $this->notify($this->customerUser, ['audience' => 'farmer']);
        $this->assertTrue(str_contains((string) parse_url($notification->data['url'], PHP_URL_PATH), '/farmer/'));

        $this->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'));

        // Unread state was still cleared.
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function mark_read_rejects_non_http_url_payloads(): void
    {
        $this->actAsCustomer();
        $notification = $this->notify($this->customerUser, ['audience' => 'customer']);
        $notification->forceFill(['data' => array_merge($notification->data, ['url' => 'javascript:alert(1)'])])->save();

        $this->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'));
    }

    /** @test */
    public function foreign_notifications_cannot_be_marked_read(): void
    {
        $this->actAsFarmer();
        $notification = $this->notify($this->customerUser, ['audience' => 'customer']);

        $this->post(route('notifications.read', $notification))->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    /** @test */
    public function count_endpoint_reports_unread_total(): void
    {
        $this->actAsCustomer();
        $this->notify($this->customerUser, ['audience' => 'farmer']);

        $this->get(route('notifications.count'))
            ->assertOk()
            ->assertJson(['unread' => 1]);
    }
}
