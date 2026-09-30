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

        $this->post(route('checkout.place'), [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertRedirect(route('checkout.index'))->assertSessionHas('error');

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

        $this->post(route('checkout.place'), [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertRedirect(route('orders.index'));

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

        // Web requests get a redirect + message; the JSON path (widget) gets a 422.
        $this->from(route('cart.index'))
            ->post(route('cart.add'), [
                'stock_id' => $this->stock->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->postJson(route('cart.add'), [
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

        $this->post(route('checkout.place'), [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertRedirect(route('checkout.index'))->assertSessionHas('error');

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

        $this->post(route('checkout.place'), [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertRedirect(route('checkout.index'))->assertSessionHas('error');

        $this->assertEquals(0, Order::count());
    }

    /** @test */
    public function inactive_market_cannot_be_ordered(): void
    {
        $this->actAsCustomer();
        $this->addToCart(2);
        $this->market->update(['status' => 'inactive']);

        $this->post(route('checkout.place'), [
            'slots' => [$this->stall->id => $this->slot->id],
        ])->assertRedirect(route('checkout.index'))->assertSessionHas('error');

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
        $this->post(route('checkout.place'), ['slots' => [$this->stall->id => $this->slot->id]]);
        $this->stock->refresh();
        $this->assertEquals(45.0, (float) $this->stock->available_quantity);

        $order = Order::latest('id')->first();
        $this->actAsFarmer();
        $this->post(route('farmer.orders.decline', $order))->assertRedirect();

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
        $this->post(route('checkout.place'), ['slots' => [$this->stall->id => $this->slot->id]]);
        $order = Order::latest('id')->first();

        $this->actAsFarmer();
        $this->post(route('farmer.orders.decline', $order))->assertRedirect();
        $this->post(route('farmer.orders.decline', $order))->assertSessionHas('error');

        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);
    }

    /** @test */
    public function double_cancelling_cannot_restore_stock_twice(): void
    {
        $this->actAsCustomer();
        $this->addToCart(5);
        $this->post(route('checkout.place'), ['slots' => [$this->stall->id => $this->slot->id]]);
        $order = Order::latest('id')->first();

        $this->post(route('orders.cancel', $order))->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(50.0, (float) $this->stock->available_quantity);

        // Second cancel must be refused — no double restore.
        $this->post(route('orders.cancel', $order))->assertSessionHas('error');
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
        $this->post(route('checkout.place'), ['slots' => [$this->stall->id => $this->slot->id]]);

        $this->stock->refresh();
        $this->assertEquals(40.0, (float) $this->stock->available_quantity);

        // Farmer re-saves the week with a bigger total — the 10 sold units
        // must stay sold, not come back as sellable stock.
        $this->actAsFarmer();
        $this->post(route('farmer.stock.update', $this->stock), [
            'quantity' => 60,
            'status' => 'available',
        ])->assertRedirect();

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

        $this->get(route('products.show', $this->product->id))->assertNotFound();
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

        $response = $this->get(route('products.show', $this->product->id));
        $response->assertOk();

        // Only the current-week row is listed.
        $this->assertEquals(1, $response->viewData('stocks')->count());
    }

    /** @test */
    public function farmer_page_hides_stock_of_inactive_products(): void
    {
        $this->get(route('farmers.show', $this->farmer->id))->assertOk();

        $this->product->update(['status' => 'inactive']);

        $response = $this->get(route('farmers.show', $this->farmer->id))->assertOk();
        $this->assertEquals(0, $response->viewData('stocks')->count());
    }

    // ---------------------------------------------------------------
    // Pickup slots
    // ---------------------------------------------------------------

    /** @test */
    public function declined_orders_do_not_consume_slot_capacity(): void
    {
        $this->actAsCustomer();
        $this->addToCart(1);
        $this->post(route('checkout.place'), ['slots' => [$this->stall->id => $this->slot->id]]);
        $order = Order::latest('id')->first();

        // Slot capacity is 1 (max_orders=1) — farmer declines, freeing it.
        $this->actAsFarmer();
        $this->post(route('farmer.orders.decline', $order))->assertRedirect();

        // Stock was restored by the decline, so a second customer books the
        // same slot successfully.
        $user2 = User::create([
            'name' => 'C2', 'email' => 'c2@test.dev', 'phone' => '400',
            'role' => 'customer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
        \App\Models\CustomerProfile::create(['user_id' => $user2->id, 'address' => '2 Customer Street']);

        $this->actingAs($user2)->post(route('cart.add'), [
            'stock_id' => $this->stock->id,
            'quantity' => 1,
        ])->assertSessionHas('success');

        $this->post(route('checkout.place'), ['slots' => [$this->stall->id => $this->slot->id]])
            ->assertRedirect(route('orders.index'));

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

        $this->post(route('farmer.slots.store'), $payload)->assertRedirect(); // created
        $this->post(route('farmer.slots.store'), $payload) // duplicate
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(1, PickupSlot::where('farmer_market_id', $this->stall->id)
            ->where('start_time', '14:00')->count());
    }

    /** @test */
    public function slot_cutoff_must_be_before_the_pickup_window_ends(): void
    {
        $this->actAsFarmer();

        $this->post(route('farmer.slots.store'), [
            'farmer_market_id' => $this->stall->id,
            'pickup_date' => now()->addDays(3)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'cutoff_at' => now()->addDays(3)->addHours(20)->toDateTimeString(), // after the 16:00 end
            'max_orders' => 5,
        ])->assertSessionHasErrors();

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

        $this->actingAs($user)->get(route('farmer.dashboard'))
            ->assertRedirect(route('login'));

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

        $this->actingAs($user)->get(route('farmer.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** @test */
    public function approved_farmers_still_use_the_farmer_area(): void
    {
        $this->actAsFarmer()->get(route('farmer.dashboard'))->assertOk();
    }
}
