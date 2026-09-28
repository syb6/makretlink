<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['farmer_id'] = $this->farmerId();
        $data['slug'] = $this->uniqueSlug($data['name'], $this->farmerId());

        if ($request->hasFile('image')) {
            $data['image'] = ImageLibrary::replace($request->file('image'), 'product', null);
        }

        Product::create($data);

        return back()->with('success', 'Product added.');
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->farmer_id === $this->farmerId(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:2048'],
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
