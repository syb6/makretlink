<?php

namespace Tests\Concerns;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\FarmerMarket;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Order;
use App\Models\PickupSlot;
use App\Models\Product;
use App\Models\User;
use App\Models\WeeklyStock;
use Illuminate\Support\Facades\Hash;

/**
 * Builds the full domain graph needed for order-flow tests:
 * admin + farmer + customer, a market with a stall, a product,
 * fresh weekly stock and a bookable pickup slot.
 */
trait CreatesMarketLinkData
{
    protected User $admin;
    protected User $farmerUser;
    protected User $customerUser;
    protected FarmerProfile $farmer;
    protected CustomerProfile $customer;
    protected Market $market;
    protected FarmerMarket $stall;
    protected Product $product;
    protected WeeklyStock $stock;
    protected PickupSlot $slot;

    protected function setUpMarketLink(): void
    {
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.dev', 'phone' => '100',
            'role' => 'admin', 'status' => 'active', 'password' => Hash::make('password'),
        ]);

        $this->farmerUser = User::create([
            'name' => 'Farmer', 'email' => 'farmer@test.dev', 'phone' => '200',
            'role' => 'farmer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
        $this->farmer = FarmerProfile::create([
            'user_id' => $this->farmerUser->id,
            'business_name' => 'Test Farm',
            'contact_person' => 'Farmer',
            'address' => '1 Farm Road',
            'approval_status' => 'approved',
        ]);

        $this->customerUser = User::create([
            'name' => 'Customer', 'email' => 'customer@test.dev', 'phone' => '300',
            'role' => 'customer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
        $this->customer = CustomerProfile::create([
            'user_id' => $this->customerUser->id,
            'address' => '1 Customer Street',
        ]);

        $this->market = Market::create([
            'name' => 'Test Market', 'address' => '1 Market Square', 'status' => 'active',
        ]);

        $this->stall = FarmerMarket::create([
            'farmer_id' => $this->farmer->id,
            'market_id' => $this->market->id,
            'stall_name' => 'Test Stall',
            'status' => 'active',
        ]);

        $category = Category::create(['name' => 'Vegetables', 'slug' => 'vegetables', 'status' => 'active']);

        $this->product = Product::create([
            'farmer_id' => $this->farmer->id,
            'category_id' => $category->id,
            'name' => 'Tomatoes', 'slug' => 'tomatoes',
            'price' => 4.50, 'unit' => 'kg', 'status' => 'active',
        ]);

        $this->stock = WeeklyStock::create([
            'product_id' => $this->product->id,
            'farmer_market_id' => $this->stall->id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'quantity' => 50, 'available_quantity' => 50, 'status' => 'available',
        ]);

        $this->slot = PickupSlot::create([
            'farmer_market_id' => $this->stall->id,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00', 'end_time' => '12:00',
            'cutoff_at' => now()->addDay(),
            'max_orders' => 10, 'status' => 'active',
        ]);
    }

    protected function actAsCustomer(): self
    {
        return $this->actingAs($this->customerUser);
    }

    protected function actAsFarmer(): self
    {
        return $this->actingAs($this->farmerUser);
    }

    protected function actAsAdmin(): self
    {
        return $this->actingAs($this->admin);
    }

    protected function addToCart(float $qty = 2): CartItem
    {
        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'farmer_market_id' => $this->stall->id,
            'quantity' => $qty,
            'price' => $this->product->price,
        ]);
    }

    protected function createOrder(string $status = 'placed', float $qty = 2): Order
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $this->customer->id,
            'farmer_market_id' => $this->stall->id,
            'pickup_slot_id' => $this->slot->id,
            'customer_name' => $this->customerUser->name,
            'customer_email' => $this->customerUser->email,
            'farmer_name' => $this->farmer->business_name,
            'market_name' => $this->market->name,
            'pickup_date' => $this->slot->pickup_date,
            'pickup_start_time' => $this->slot->start_time,
            'pickup_end_time' => $this->slot->end_time,
            'status' => $status,
            'total_amount' => $qty * $this->product->price,
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit' => $this->product->unit,
            'quantity' => $qty,
            'unit_price' => $this->product->price,
            'subtotal' => $qty * $this->product->price,
        ]);

        // Every order starts with a 'placed' history row (same as real checkout).
        $order->statusHistories()->create([
            'status' => 'placed',
            'changed_by' => $this->customerUser->id,
            'note' => 'Order placed by customer.',
            'changed_at' => now(),
        ]);

        return $order;
    }
}
