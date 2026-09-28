<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FarmerMarket;
use App\Models\Product;
use App\Models\WeeklyStock;
use App\Services\ImageLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private function farmerId(): int
    {
        return Auth::user()->farmerProfile->id;
    }

    public function index()
    {
        $products = Product::with('category')
            ->where('farmer_id', $this->farmerId())
            ->withTrashed()
            ->latest()
            ->paginate(10);

        return view('farmer.products.index', [
            'products' => $products,
            'categories' => Category::where('status', 'active')->orderBy('name')->get(),
            'stalls' => FarmerMarket::with('market')
                ->where('farmer_id', $this->farmerId())
                ->where('status', 'active')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
            'status' => ['required', 'in:active,inactive'],

            // Optional launch stock: without it a brand-new product has no
            // weekly stock row, and the public /products page lists weekly
            // stock — the product would never show up until the farmer also
            // opened the Weekly Stock page. Lets it go live immediately.
            'initial_market_id' => ['nullable', 'exists:farmer_markets,id'],
            'initial_quantity' => ['nullable', 'numeric', 'min:0', 'max:100000', 'required_with:initial_market_id'],
        ]);

        $farmerId = $this->farmerId();
        $data['farmer_id'] = $farmerId;
        $data['slug'] = $this->uniqueSlug($data['name'], $farmerId);

        if ($request->hasFile('image')) {
            $data['image'] = ImageLibrary::replace($request->file('image'), 'product', null);
        }

        $product = Product::create($data);

        if (! empty($data['initial_market_id'])) {
            // Only the farmer's own active stall may be stocked.
            abort_unless(
                FarmerMarket::where('id', $data['initial_market_id'])
                    ->where('farmer_id', $farmerId)
                    ->where('status', 'active')
                    ->exists(),
                403
            );

            WeeklyStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'farmer_market_id' => $data['initial_market_id'],
                    'week_start' => now()->startOfWeek(Carbon::SUNDAY)->toDateString(),
                ],
                [
                    'quantity' => $data['initial_quantity'],
                    'available_quantity' => $data['initial_quantity'],
                    'status' => $data['initial_quantity'] > 0 ? 'available' : 'unavailable',
                ]
            );
        }

        return back()->with(
            'success',
            'Product added.'.(! empty($data['initial_market_id']) ? ' This week\'s stock is live.' : ' Add weekly stock to make it visible to customers.')
        );
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->farmer_id === $this->farmerId(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = ImageLibrary::replace($request->file('image'), 'product', $product->image);
        }

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        abort_unless($product->farmer_id === $this->farmerId(), 403);

        $product->delete(); // soft delete

        // Free the upload from disk so storage doesn't fill with orphaned
        // pictures (only farmer uploads are removed — shared seeder assets
        // and images still referenced elsewhere are left alone).
        ImageLibrary::delete('product', $product->image);

        return back()->with('success', 'Product removed.');
    }

    public function restore($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        abort_unless($product->farmer_id === $this->farmerId(), 403);

        $product->restore();

        return back()->with('success', 'Product restored.');
    }

    private function uniqueSlug(string $name, int $farmerId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('farmer_id', $farmerId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
