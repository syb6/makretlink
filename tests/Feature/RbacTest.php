<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\FarmerMarket;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreatesMarketLinkData;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase, CreatesMarketLinkData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMarketLink();
    }

    private function loginAs(User $user): void
    {
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk();
    }

    /** @test */
    public function guests_receive_json_unauthorized_for_protected_api_areas(): void
    {
        foreach ([
            '/api/customer/dashboard',
            '/api/farmer/dashboard',
            '/api/admin/dashboard',
            '/api/customer/orders',
            '/api/notifications',
        ] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    /** @test */
    public function customer_area_is_forbidden_for_other_roles(): void
    {
        $this->loginAs($this->admin);
        $this->getJson('/api/customer/dashboard')->assertForbidden();

        $this->postJson('/api/auth/logout');
        $this->loginAs($this->farmerUser);
        $this->getJson('/api/customer/dashboard')->assertForbidden();
    }

    /** @test */
    public function farmer_area_is_forbidden_for_other_roles(): void
    {
        $this->loginAs($this->customerUser);
        $this->getJson('/api/farmer/dashboard')->assertForbidden();

        $this->postJson('/api/auth/logout');
        $this->loginAs($this->admin);
        $this->getJson('/api/farmer/dashboard')->assertForbidden();
    }

    /** @test */
    public function admin_area_is_forbidden_for_other_roles(): void
    {
        $this->loginAs($this->customerUser);
        $this->getJson('/api/admin/dashboard')->assertForbidden();

        $this->postJson('/api/auth/logout');
        $this->loginAs($this->farmerUser);
        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }

    /** @test */
    public function each_role_redirects_to_its_own_dashboard_after_login(): void
    {
        $this->postJson('/api/auth/login', ['email' => $this->customerUser->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('redirect', '/customer/dashboard');
        $this->getJson('/api/me')->assertJsonPath('user.role', 'customer');

        $this->postJson('/api/auth/logout');

        $this->postJson('/api/auth/login', ['email' => $this->farmerUser->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('redirect', '/farmer/dashboard');
        $this->getJson('/api/me')->assertJsonPath('user.role', 'farmer');

        $this->postJson('/api/auth/logout');

        $this->postJson('/api/auth/login', ['email' => $this->admin->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('redirect', '/admin/dashboard');
        $this->getJson('/api/me')->assertJsonPath('user.role', 'admin');
    }

    /** @test */
    public function inactive_accounts_cannot_log_in(): void
    {
        $this->customerUser->update(['status' => 'suspended']);

        $this->postJson('/api/auth/login', ['email' => $this->customerUser->email, 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonStructure(['validation' => ['email']]);

        $this->assertGuest();
    }

    /** @test */
    public function suspended_users_are_logged_out_when_browsing_authed_pages(): void
    {
        $this->actAsCustomer();
        $this->customerUser->update(['status' => 'suspended']);
        Auth::forgetGuards();

        $this->getJson('/api/customer/dashboard')
            ->assertOk()
            ->assertJsonPath('flash.error', 'Your account has been deactivated. Contact the administrator.');

        $this->assertGuest();
    }

    /** @test */
    public function customer_cannot_read_or_cancel_another_customers_order(): void
    {
        $otherCustomer = User::create([
            'name' => 'Other', 'email' => 'other@test.dev', 'phone' => '400',
            'role' => 'customer', 'status' => 'active', 'password' => 'x',
        ]);
        \App\Models\CustomerProfile::create(['user_id' => $otherCustomer->id, 'address' => 'x']);

        $order = $this->createOrder('placed'); // belongs to $this->customer

        $this->actingAs($otherCustomer);
        $this->getJson("/api/customer/orders/{$order->id}")->assertForbidden();
        $this->postJson("/api/customer/orders/{$order->id}/cancel")->assertForbidden();

        // DB state untouched
        $this->assertEquals('placed', $order->fresh()->status);
    }

    /** @test */
    public function farmer_cannot_manage_orders_from_another_farmers_stall(): void
    {
        // Second farmer with their own stall + order
        $otherFarmerUser = User::create([
            'name' => 'Farmer2', 'email' => 'farmer2@test.dev', 'phone' => '500',
            'role' => 'farmer', 'status' => 'active', 'password' => 'x',
        ]);
        $otherFarmer = FarmerProfile::create([
            'user_id' => $otherFarmerUser->id,
            'business_name' => 'Other Farm', 'contact_person' => 'F2', 'address' => 'x',
            'approval_status' => 'approved',
        ]);
        $otherStall = \App\Models\FarmerMarket::create([
            'farmer_id' => $otherFarmer->id, 'market_id' => $this->market->id, 'status' => 'active',
        ]);

        $order = $this->createOrder('placed');
        $order->update(['farmer_market_id' => $otherStall->id]);

        $this->actAsFarmer(); // our farmer, not the owner
        $this->postJson("/api/farmer/orders/{$order->id}/accept")->assertForbidden();
        $this->getJson("/api/farmer/orders/{$order->id}")->assertForbidden();

        $this->assertEquals('placed', $order->fresh()->status);
    }

    /** @test */
    public function farmer_cannot_edit_another_farmers_product(): void
    {
        $otherProduct = Product::create([
            'farmer_id' => $this->farmer->id, // will be reassigned below
            'category_id' => $this->product->category_id,
            'name' => 'Other Product', 'slug' => 'other-product',
            'price' => 1, 'unit' => 'kg', 'status' => 'active',
        ]);

        // Reassign to a different farmer
        $otherFarmerUser = User::create([
            'name' => 'Farmer3', 'email' => 'farmer3@test.dev', 'phone' => '600',
            'role' => 'farmer', 'status' => 'active', 'password' => 'x',
        ]);
        $otherFarmer = FarmerProfile::create([
            'user_id' => $otherFarmerUser->id,
            'business_name' => 'Farm Three', 'contact_person' => 'F3', 'address' => 'x',
            'approval_status' => 'approved',
        ]);
        $otherProduct->update(['farmer_id' => $otherFarmer->id]);

        $this->actAsFarmer();
        $this->postJson("/api/farmer/products/{$otherProduct->id}", [
            'name' => 'Hacked', 'price' => 0.01, 'unit' => 'kg', 'status' => 'active',
        ])->assertForbidden();

        $this->assertEquals('Other Product', $otherProduct->fresh()->name);
    }

    /** @test */
    public function notifications_are_private_to_their_owner(): void
    {
        $order = $this->createOrder('placed');
        $this->farmerUser->notify(new \App\Notifications\OrderStatusUpdate($order, 'placed', null, 'farmer'));

        $notification = $this->farmerUser->notifications()->first();

        $this->actAsCustomer();
        $this->postJson("/api/notifications/{$notification->id}/read")->assertForbidden();
    }

    /** @test */
    public function cart_items_belong_to_their_owner(): void
    {
        $this->actAsCustomer();
        $item = $this->addToCart(2);

        $otherCustomer = User::create([
            'name' => 'Other2', 'email' => 'other2@test.dev', 'phone' => '700',
            'role' => 'customer', 'status' => 'active', 'password' => 'x',
        ]);
        $this->actingAs($otherCustomer);

        $this->postJson("/api/customer/cart/{$item->id}/update", ['quantity' => 99])->assertForbidden();
        $this->postJson("/api/customer/cart/{$item->id}/remove")->assertForbidden();

        $this->assertEquals(2, $item->fresh()->quantity);
    }

    /** @test */
    public function order_scoping_hides_foreign_orders_from_farmer_listing(): void
    {
        // Order on our farmer's stall
        $mine = $this->createOrder('placed');

        // Order on another farmer's stall
        $otherFarmerUser = User::create([
            'name' => 'Farmer4', 'email' => 'farmer4@test.dev', 'phone' => '800',
            'role' => 'farmer', 'status' => 'active', 'password' => 'x',
        ]);
        $otherFarmer = FarmerProfile::create([
            'user_id' => $otherFarmerUser->id,
            'business_name' => 'Farm Four', 'contact_person' => 'F4', 'address' => 'x',
            'approval_status' => 'approved',
        ]);
        $otherStall = \App\Models\FarmerMarket::create([
            'farmer_id' => $otherFarmer->id, 'market_id' => $this->market->id, 'status' => 'active',
        ]);

        $other = $this->createOrder('placed');
        $other->update(['farmer_market_id' => $otherStall->id]);

        $this->actAsFarmer();
        $response = $this->getJson('/api/farmer/orders');

        $response->assertOk();
        $ids = collect($response->json('rows'))->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($other->id));
    }
}
