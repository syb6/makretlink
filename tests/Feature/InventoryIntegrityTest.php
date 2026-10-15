<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PickupSlot;
use App\Models\User;
use App\Models\FarmerProfile;
use App\Models\WeeklyStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesMarketLinkData;
use Tests\TestCase;

class InventoryIntegrityTest extends TestCase
{
    use RefreshDatabase, CreatesMarketLinkData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMarketLink();
    }

    private function makeFarmerUser(string $email): User
    {
        return User::create([
            'name' => 'Farmer X', 'email' => $email, 'phone' => '900',
            'role' => 'farmer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
    }

    // ---------------------------------------------------------------
    // Checkout stock recheck / oversell / live pricing
    // ---------------------------------------------------------------

    /** @test */
    public function checkout_rejects_oversell_instead_of_clamping(): void
    {
        $this->actAsCustomer();

        // Cart holds 40; stock quietly drops to 10 after the add.
        $this->addToCart(40);
        $this->stock->update(['available_quantity' => 10]);

        $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertOk()->assertJsonPath('ok', false);

        // Nothing sold, nothing ordered, item stays in the cart.
        $this->stock->refresh();
        $this->assertEquals(10.0, (float) $this->stock->available_quantity);
        $this->assertEquals(0, Order::count());
    }

    /** @test */
    public function checkout_charges_the_live_price_not_the_cart_price(): void
    {
        $this->actAsCustomer();
        $this->addToCart(2); // cart stored Rs 4.50

        // Farmer raises the price AFTER the item was added.
        $this->product->update(['price' => 6.00]);

        $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertOk()->assertJsonPath('redirect', '/customer/orders');

        $order = Order::latest('id')->first();
        $this->assertEquals(12.00, (float) $order->total_amount); // 2 x 6.00
        $this->assertEquals(6.00, (float) $order->items->first()->unit_price);
    }

    // ---------------------------------------------------------------
    // Sellable guards (product active, farmer approved, stall/market
    // active, week current)
    // ---------------------------------------------------------------

    /** @test */
    public function old_week_stock_cannot_be_added_to_the_cart(): void
    {
        $this->actAsCustomer();
        $this->stock->update(['week_start' => now()->subWeek()->startOfWeek()->toDateString()]);

        $this->postJson('/api/customer/cart/add', [
                'stock_id' => $this->stock->id,
                'quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This item is no longer available.');

        $this->postJson('/api/customer/cart/add', [
            'stock_id' => $this->stock->id,
            'quantity' => 1,
        ])->assertStatus(422);
    }

    /** @test */
    public function inactive_product_cannot_be_ordered(): void
    {
        $this->actAsCustomer();
        $this->addToCart(2);
        $this->product->update(['status' => 'inactive']);

        $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertOk()->assertJsonPath('ok', false);

        $this->assertEquals(0, Order::count());
        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);
    }

    /** @test */
    public function inactive_stall_cannot_be_ordered(): void
    {
        $this->actAsCustomer();
        $this->addToCart(2);
        $this->stall->update(['status' => 'inactive']);

        $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertOk()->assertJsonPath('ok', false);

        $this->assertEquals(0, Order::count());
    }

    /** @test */
    public function inactive_market_cannot_be_ordered(): void
    {
        $this->actAsCustomer();
        $this->addToCart(2);
        $this->market->update(['status' => 'inactive']);

        $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertOk()->assertJsonPath('ok', false);

        $this->assertEquals(0, Order::count());
    }

    // ---------------------------------------------------------------
    // Decline restores stock / double-click races
    // ---------------------------------------------------------------

    /** @test */
    public function declining_an_order_restores_the_reserved_stock(): void
    {
        $this->actAsCustomer();
        $this->addToCart(5);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);
        $this->stock->refresh();
        $this->assertEquals(45.0, (float) $this->stock->available_quantity);

        $order = Order::latest('id')->first();
        $this->actAsFarmer();
        $this->postJson("/api/farmer/orders/{$order->id}/decline")->assertOk();

        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);
        $this->assertEquals('available', $this->stock->status);
        $this->assertEquals('declined', $order->fresh()->status);
    }

    /** @test */
    public function double_declining_cannot_restore_stock_twice(): void
    {
        $this->actAsCustomer();
        $this->addToCart(5);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);
        $order = Order::latest('id')->first();

        $this->actAsFarmer();
        $this->postJson("/api/farmer/orders/{$order->id}/decline")->assertOk();
        $this->postJson("/api/farmer/orders/{$order->id}/decline")->assertOk()->assertJsonPath('ok', false);

        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);
    }

    /** @test */
    public function double_cancelling_cannot_restore_stock_twice(): void
    {
        $this->actAsCustomer();
        $this->addToCart(5);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);
        $order = Order::latest('id')->first();

        $this->postJson("/api/customer/orders/{$order->id}/cancel")->assertOk();
        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);

        // Second cancel must be refused — no double restore.
        $this->postJson("/api/customer/orders/{$order->id}/cancel")->assertOk()->assertJsonPath('ok', false);
        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);
    }

    // ---------------------------------------------------------------
    // Weekly stock re-save keeps reservations
    // ---------------------------------------------------------------

    /** @test */
    public function re_saving_weekly_stock_preserves_units_already_sold(): void
    {
        $this->actAsCustomer();
        $this->addToCart(10);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);

        $this->stock->refresh();
        $this->assertEquals(40.0, (float) $this->stock->available_quantity);

        // Farmer re-saves the week with a bigger total — the 10 sold units
        // must stay sold, not come back as sellable stock.
        $this->actAsFarmer();
        $this->postJson("/api/farmer/stock/{$this->stock->id}", [
            'quantity' => 60,
            'status' => 'available',
        ])->assertOk();

        $this->stock->refresh();
        $this->assertEquals(60.0, (float) $this->stock->quantity);
        $this->assertEquals(50.0, (float) $this->stock->available_quantity); // 60 - 10 sold
    }

    // ---------------------------------------------------------------
    // Public page leaks
    // ---------------------------------------------------------------

    /** @test */
    public function product_page_is_404_when_farmer_is_not_approved(): void
    {
        $this->farmer->update(['approval_status' => 'pending']);

        $this->getJson("/api/products/{$this->product->id}")->assertNotFound();
    }

    /** @test */
    public function product_page_does_not_list_old_week_stock(): void
    {
        WeeklyStock::create([
            'product_id' => $this->product->id,
            'farmer_market_id' => $this->stall->id,
            'week_start' => now()->subWeek()->startOfWeek()->toDateString(),
            'quantity' => 30, 'available_quantity' => 30, 'status' => 'available',
        ]);

        $response = $this->getJson("/api/products/{$this->product->id}");
        $response->assertOk();

        // Only the current-week row is listed.
        $this->assertCount(1, $response->json('stocks'));
    }

    /** @test */
    public function farmer_page_hides_stock_of_inactive_products(): void
    {
        $this->getJson("/api/farmers/{$this->farmer->id}")->assertOk();

        $this->product->update(['status' => 'inactive']);

        $response = $this->getJson("/api/farmers/{$this->farmer->id}")->assertOk();
        $this->assertCount(0, $response->json('stocks'));
    }

    // ---------------------------------------------------------------
    // Pickup slots
    // ---------------------------------------------------------------

    /** @test */
    public function declined_orders_do_not_consume_slot_capacity(): void
    {
        $this->actAsCustomer();
        $this->addToCart(1);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);
        $order = Order::latest('id')->first();

        // Slot capacity is 1 (max_orders=1) — farmer declines, freeing it.
        $this->actAsFarmer();
        $this->postJson("/api/farmer/orders/{$order->id}/decline")->assertOk();

        // Stock was restored by the decline, so a second customer books the
        // same slot successfully.
        $user2 = User::create([
            'name' => 'C2', 'email' => 'c2@test.dev', 'phone' => '400',
            'role' => 'customer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
        \App\Models\CustomerProfile::create(['user_id' => $user2->id, 'address' => '2 Customer Street']);

        $this->postJson('/api/auth/login', ['email' => $user2->email, 'password' => 'password'])->assertOk();
        $this->postJson('/api/customer/cart/add', [
            'stock_id' => $this->stock->id,
            'quantity' => 1,
        ])->assertOk()->assertJsonPath('ok', true);

        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]])
            ->assertOk()
            ->assertJsonPath('redirect', '/customer/orders');

        $this->assertEquals(2, Order::count());
    }

    /** @test */
    public function duplicate_slot_is_rejected_with_an_error_not_a_500(): void
    {
        $this->actAsFarmer();

        $payload = [
            'farmer_market_id' => $this->stall->id,
            'pickup_date' => now()->addDays(3)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'cutoff_at' => now()->addDays(2)->toDateTimeString(),
            'max_orders' => 5,
        ];

        $this->postJson('/api/farmer/slots', $payload)->assertOk(); // created
        $this->postJson('/api/farmer/slots', $payload) // duplicate
            ->assertOk()
            ->assertJsonPath('ok', false);

        $this->assertEquals(1, PickupSlot::where('farmer_market_id', $this->stall->id)
            ->where('start_time', '14:00')->count());
    }

    /** @test */
    public function slot_cutoff_must_be_before_the_pickup_window_ends(): void
    {
        $this->actAsFarmer();

        $this->postJson('/api/farmer/slots', [
            'farmer_market_id' => $this->stall->id,
            'pickup_date' => now()->addDays(3)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'cutoff_at' => now()->addDays(3)->addHours(20)->toDateTimeString(), // after the 16:00 end
            'max_orders' => 5,
        ])->assertUnprocessable();

        $this->assertEquals(0, PickupSlot::where('farmer_market_id', $this->stall->id)
            ->where('start_time', '14:00')->count());
    }

    // ---------------------------------------------------------------
    // Farmer approval gate
    // ---------------------------------------------------------------

    /** @test */
    public function pending_farmers_cannot_use_the_farmer_area(): void
    {
        $user = $this->makeFarmerUser('pending@test.dev');
        FarmerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Pending Farm',
            'contact_person' => 'P', 'address' => 'X', 'approval_status' => 'pending',
        ]);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->getJson('/api/farmer/dashboard')
            ->assertOk()
            ->assertJsonPath('ok', false);

        $this->assertGuest();
    }

    /** @test */
    public function rejected_farmers_cannot_use_the_farmer_area(): void
    {
        $user = $this->makeFarmerUser('rejected@test.dev');
        FarmerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Rejected Farm',
            'contact_person' => 'R', 'address' => 'X', 'approval_status' => 'rejected',
        ]);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->getJson('/api/farmer/dashboard')
            ->assertOk()
            ->assertJsonPath('ok', false);

        $this->assertGuest();
    }

    /** @test */
    public function approved_farmers_still_use_the_farmer_area(): void
    {
        $this->actAsFarmer()->getJson('/api/farmer/dashboard')->assertOk();
    }
}
