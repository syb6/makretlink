<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\WeeklyStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMarketLinkData;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase, CreatesMarketLinkData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMarketLink();
    }

    /** @test */
    public function customer_can_place_an_order_from_the_cart(): void
    {
        $this->actAsCustomer();
        $this->addToCart(2);

        $response = $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ]);

        $response->assertOk()->assertJsonPath('redirect', '/customer/orders');

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('placed', $order->status);
        $this->assertEquals(9.00, (float) $order->total_amount); // 2kg x Rs 4.50
        $this->assertCount(1, $order->items);
        $this->assertEquals('Customer', $order->customer_name);
        $this->assertEquals($this->farmer->business_name, $order->farmer_name);

        // Cart is emptied after checkout
        $this->assertEquals(0, CartItem::where('cart_id', $order->customer_id)->count());

        // Status history starts with 'placed'
        $this->assertTrue($order->statusHistories()->where('status', 'placed')->exists());
    }

    /** @test */
    public function placing_an_order_deducts_weekly_stock(): void
    {
        $this->actAsCustomer();
        $this->addToCart(5);

        $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertOk();

        $this->stock->refresh();
        $this->assertEquals(45.0, (float) $this->stock->available_quantity);
        $this->assertEquals('available', $this->stock->status);
    }

    /** @test */
    public function stock_marks_sold_out_when_availability_hits_zero(): void
    {
        $this->actAsCustomer();
        $this->addToCart(50); // everything

        $this->postJson('/api/customer/checkout/place', [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertOk();

        $this->stock->refresh();
        $this->assertEquals(0.0, (float) $this->stock->available_quantity);
        $this->assertEquals('sold_out', $this->stock->status);
    }

    /** @test */
    public function checkout_requires_a_pickup_slot_for_every_stall(): void
    {
        $this->actAsCustomer();
        $this->addToCart(2);

        $this->postJson('/api/customer/checkout/place', ['slots' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slots');

        $this->assertEquals(0, Order::count());
    }

    /** @test */
    public function full_farmer_acceptance_flow_walks_all_statuses(): void
    {
        $order = $this->createOrder('placed');
        $this->actAsFarmer();

        // placed -> accepted
        $this->postJson("/api/farmer/orders/{$order->id}/accept")->assertOk();
        $this->assertEquals('accepted', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->accepted_at);

        // accepted -> ready_for_pickup
        $this->postJson("/api/farmer/orders/{$order->id}/ready")->assertOk();
        $this->assertEquals('ready_for_pickup', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->ready_at);

        // ready_for_pickup -> completed
        $this->postJson("/api/farmer/orders/{$order->id}/complete")->assertOk();
        $this->assertEquals('completed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);

        // Every transition left exactly one history row, in order
        $statuses = $order->statusHistories()->orderBy('id')->pluck('status')->all();
        $this->assertEquals(['placed', 'accepted', 'ready_for_pickup', 'completed'], $statuses);
    }

    /** @test */
    public function farmer_can_decline_a_new_order(): void
    {
        $order = $this->createOrder('placed');
        $this->actAsFarmer();

        $this->postJson("/api/farmer/orders/{$order->id}/decline")->assertOk();

        $this->assertEquals('declined', $order->fresh()->status);
        $this->assertEquals(['placed', 'declined'], $order->statusHistories()->orderBy('id')->pluck('status')->all());
    }

    /** @test */
    public function invalid_transitions_are_rejected(): void
    {
        // Cannot accept an already-accepted order
        $accepted = $this->createOrder('accepted');
        $this->actAsFarmer();
        $this->postJson("/api/farmer/orders/{$accepted->id}/accept")->assertOk()->assertJsonPath('ok', false);

        // Cannot complete an order that was never marked ready
        $placed = $this->createOrder('placed');
        $this->postJson("/api/farmer/orders/{$placed->id}/complete")->assertOk()->assertJsonPath('ok', false);

        $this->assertEquals('accepted', $accepted->fresh()->status);
        $this->assertEquals('placed', $placed->fresh()->status);
    }

    /** @test */
    public function customer_can_cancel_before_cutoff_and_stock_is_restored(): void
    {
        $this->actAsCustomer();

        // Place a real order through checkout so stock is deducted
        $this->addToCart(5);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);
        $order = Order::latest('id')->first();

        $this->stock->refresh();
        $this->assertEquals(45.0, (float) $this->stock->available_quantity);

        $this->postJson("/api/customer/orders/{$order->id}/cancel")->assertOk();

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);

        // Cancelled units return to the weekly stock
        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);
        $this->assertEquals(['placed', 'cancelled'], $order->statusHistories()->orderBy('id')->pluck('status')->all());
    }

    /** @test */
    public function customer_cannot_cancel_after_the_cutoff_time(): void
    {
        $this->actAsCustomer();

        $order = $this->createOrder('accepted');
        $order->pickupSlot->update(['cutoff_at' => now()->subHour()]);

        $this->postJson("/api/customer/orders/{$order->id}/cancel")->assertOk()->assertJsonPath('ok', false);
        $this->assertEquals('accepted', $order->fresh()->status);
    }

    /** @test */
    public function customer_can_reorder_past_items(): void
    {
        $this->actAsCustomer();
        $order = $this->createOrder('completed');

        $response = $this->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk()->assertJsonPath('redirect', '/customer/cart');
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'farmer_market_id' => $this->stall->id,
            'quantity' => 2,
        ]);
    }

    /** @test */
    public function reviews_are_only_allowed_after_completion(): void
    {
        $this->actAsCustomer();
        $order = $this->createOrder('accepted');
        $item = $order->items->first();

        // Blocked before completion
        $this->postJson("/api/customer/orders/{$order->id}/review/product/{$item->id}", ['rating' => 5])->assertForbidden();

        // Allowed once completed
        $order->markStatus('completed', null, $this->farmerUser);
        $this->postJson("/api/customer/orders/{$order->id}/review/product/{$item->id}", [
            'rating' => 5, 'comment' => 'Great tomatoes',
        ])->assertOk();

        $this->assertDatabaseHas('product_reviews', [
            'order_id' => $order->id, 'product_id' => $this->product->id, 'rating' => 5,
        ]);
    }

    /** @test */
    public function slot_capacity_is_enforced(): void
    {
        $this->actAsCustomer();
        $this->slot->update(['max_orders' => 1]);

        $this->addToCart(1);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);

        // Fill the slot with a seeded order, then try to overbook it
        $this->addToCart(1);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);

        // First placement succeeded (1 order), second must have been blocked
        $this->assertEquals(1, Order::count());
    }

    /** @test */
    public function expired_slots_cannot_be_booked(): void
    {
        $this->actAsCustomer();
        $this->slot->update(['cutoff_at' => now()->subHour()]);

        $this->addToCart(2);
        $this->postJson('/api/customer/checkout/place', ['slots' => [$this->stall->id => $this->slot->id]]);

        $this->assertEquals(0, Order::count());
    }
}
