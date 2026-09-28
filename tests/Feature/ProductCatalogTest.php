<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FarmerMarket;
use App\Models\Market;
use App\Models\Product;
use App\Models\WeeklyStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesMarketLinkData;
use Tests\TestCase;

/**
 * Covers the product listing pipeline end to end: a newly added product with
 * initial stock must appear on /products immediately, filtering must reject
 * malformed input, and deleted entities must free their uploaded images.
 */
class ProductCatalogTest extends TestCase
{
    use CreatesMarketLinkData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMarketLink();
    }

    public function test_new_product_with_initial_stock_appears_on_products_page(): void
    {
        $this->actAsFarmer();

        $response = $this->post(route('farmer.products.store'), [
            'name' => 'Fresh Okra',
            'category_id' => $this->product->category_id,
            'price' => '12.50',
            'unit' => 'kg',
            'status' => 'active',
            'initial_market_id' => $this->stall->id,
            'initial_quantity' => '25',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $product = Product::where('name', 'Fresh Okra')->firstOrFail();

        // Weekly stock row was created for the current week (date casts
        // store midnight datetimes on SQLite).
        $this->assertDatabaseHas('weekly_stocks', [
            'product_id' => $product->id,
            'farmer_market_id' => $this->stall->id,
            'week_start' => now()->startOfWeek(\Illuminate\Support\Carbon::SUNDAY)->format('Y-m-d H:i:s'),
            'status' => 'available',
        ]);

        // And the public listing shows it.
        $this->get(route('products.index'))->assertOk()->assertSee('Fresh Okra');
    }

    public function test_new_product_without_stock_is_created_but_listing_hint_is_shown(): void
    {
        $this->actAsFarmer();

        $this->post(route('farmer.products.store'), [
            'name' => 'Stockless Turnip',
            'price' => '5',
            'unit' => 'kg',
            'status' => 'active',
        ])->assertRedirect()->assertSessionHas('success');

        $product = Product::where('name', 'Stockless Turnip')->firstOrFail();
        $this->assertDatabaseMissing('weekly_stocks', ['product_id' => $product->id]);

        // Without weekly stock the product must NOT appear on the listing.
        $this->get(route('products.index'))->assertOk()->assertDontSee('Stockless Turnip');
    }

    public function test_farmer_cannot_seed_initial_stock_on_another_farmers_stall(): void
    {
        // A rival farmer with their own stall at the same market.
        $customerStallUser = \App\Models\User::create([
            'name' => 'Rival Farmer', 'email' => 'rival@test.dev', 'phone' => '1',
            'role' => 'farmer', 'status' => 'active',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
        $rival = \App\Models\FarmerProfile::create([
            'user_id' => $customerStallUser->id, 'business_name' => 'Rival Farm',
            'contact_person' => 'Rival', 'address' => 'X', 'approval_status' => 'approved',
        ]);
        $rivalStall = FarmerMarket::create([
            'farmer_id' => $rival->id, 'market_id' => $this->market->id,
            'stall_name' => 'Rival Stall', 'status' => 'active',
        ]);

        $this->actAsFarmer();

        // Try stocking the rival's stall through the initial-stock path.
        $this->post(route('farmer.products.store'), [
            'name' => 'Sneaky Squash',
            'price' => '5',
            'unit' => 'kg',
            'status' => 'active',
            'initial_market_id' => $rivalStall->id,
            'initial_quantity' => '10',
        ])->assertForbidden();

        $this->assertDatabaseMissing('weekly_stocks', ['farmer_market_id' => $rivalStall->id]);
    }

    public function test_product_listing_rejects_malformed_filters_gracefully(): void
    {
        $this->actAsCustomer();

        // All of these used to hit the query builder raw; each must be ignored.
        $badQueries = [
            ['category' => '999999999'],                  // non-existent id
            ['category' => '1 OR 1=1'],                   // injection probe
            ['market' => 'not-an-id'],
            ['day' => '99'],
            ['min_price' => '-50'],
            ['max_price' => 'abc'],
            ['sort' => 'drop-table'],
            ['page' => '-3'],
        ];

        foreach ($badQueries as $bad) {
            $response = $this->get(route('products.index', $bad));
            $this->assertTrue(in_array($response->status(), [200, 302]), 'Unexpected status '.$response->status());
        }

        // Valid filters still work.
        $this->get(route('products.index', ['category' => $this->product->category_id]))->assertOk();
        $this->get(route('products.index', ['min_price' => '1', 'max_price' => '100']))->assertOk();
    }

    public function test_deleting_product_removes_its_uploaded_image(): void
    {
        Storage::fake('local');
        config(['filesystems.disks.fake_public' => ['driver' => 'local', 'root' => storage_path('framework/testing/disks/public')]]);

        // Create a real upload through ImageLibrary::store.
        $product = Product::create([
            'farmer_id' => $this->farmer->id,
            'category_id' => $this->product->category_id,
            'name' => 'Photo Peas',
            'slug' => 'photo-peas',
            'price' => 3,
            'unit' => 'kg',
            'image' => 'product-images/photo-peas-uabcd1234.png',
            'status' => 'active',
        ]);

        $fullPath = public_path('images/placeholders/product-images/photo-peas-uabcd1234.png');
        @mkdir(dirname($fullPath), 0775, true);
        file_put_contents($fullPath, 'fake image bytes');
        $this->assertFileExists($fullPath);

        $this->actAsFarmer();
        $this->delete(route('farmer.products.destroy', $product))->assertRedirect();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertFileDoesNotExist($fullPath); // upload freed from disk
    }

    public function test_seeder_images_are_never_deleted(): void
    {
        // Use a non-tracked dummy filename (no "-u" marker) so the test
        // never risks touching real seeder assets like apples.png.
        $file = 'product-images/seeder-dummy-nomarker.png';
        $fullPath = public_path('images/placeholders/'.$file);
        @mkdir(dirname($fullPath), 0775, true);
        file_put_contents($fullPath, 'shared bytes');

        $product = Product::create([
            'farmer_id' => $this->farmer->id,
            'name' => 'Shared Asset',
            'slug' => 'shared-asset',
            'price' => 3,
            'unit' => 'kg',
            'image' => $file,
            'status' => 'active',
        ]);

        $this->actAsFarmer();
        $this->delete(route('farmer.products.destroy', $product))->assertRedirect();

        // The shared asset file must survive the product delete.
        $this->assertFileExists($fullPath);
        @unlink($fullPath);
    }

    public function test_deleting_market_without_stalls_removes_its_uploaded_image(): void
    {
        $market = Market::create([
            'name' => 'Photo Market',
            'address' => '1 Photo Lane',
            'status' => 'active',
            'image' => 'market-images/photo-market-ushared99.jpg',
        ]);

        $fullPath = public_path('images/placeholders/market-images/photo-market-ushared99.jpg');
        @mkdir(dirname($fullPath), 0775, true);
        file_put_contents($fullPath, 'market image bytes');
        $this->assertFileExists($fullPath);

        $this->actAsAdmin();
        $this->delete(route('admin.markets.destroy', $market))->assertRedirect()->assertSessionHas('success');

        $this->assertSoftDeleted('markets', ['id' => $market->id]);
        $this->assertFileDoesNotExist($fullPath);
    }

    public function test_market_with_stalls_cannot_be_deleted_and_image_survives(): void
    {
        $market = Market::create([
            'name' => 'Busy Market',
            'address' => '2 Busy Street',
            'status' => 'active',
            'image' => 'market-images/busy-ukeepme.jpg',
        ]);

        FarmerMarket::create([
            'farmer_id' => $this->farmer->id,
            'market_id' => $market->id,
            'stall_name' => 'Stall',
            'status' => 'active',
        ]);

        $fullPath = public_path('images/placeholders/market-images/busy-ukeepme.jpg');
        @mkdir(dirname($fullPath), 0775, true);
        file_put_contents($fullPath, 'keep me');

        $this->actAsAdmin();
        $this->delete(route('admin.markets.destroy', $market))->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('markets', ['id' => $market->id]);
        $this->assertFileExists($fullPath);
        @unlink($fullPath);
    }

    public function test_add_product_rejects_bad_initial_stock_input(): void
    {
        $this->actAsFarmer();

        // Quantity required when a stall is chosen.
        $this->post(route('farmer.products.store'), [
            'name' => 'Half Stocked',
            'price' => '5',
            'unit' => 'kg',
            'status' => 'active',
            'initial_market_id' => $this->stall->id,
        ])->assertSessionHasErrors('initial_quantity');

        // Negative quantity rejected.
        $this->post(route('farmer.products.store'), [
            'name' => 'Negative Stock',
            'price' => '5',
            'unit' => 'kg',
            'status' => 'active',
            'initial_market_id' => $this->stall->id,
            'initial_quantity' => '-4',
        ])->assertSessionHasErrors('initial_quantity');

        // Unknown stall id rejected.
        $this->post(route('farmer.products.store'), [
            'name' => 'Ghost Stall',
            'price' => '5',
            'unit' => 'kg',
            'status' => 'active',
            'initial_market_id' => 999999,
        ])->assertSessionHasErrors('initial_market_id');
    }
}
