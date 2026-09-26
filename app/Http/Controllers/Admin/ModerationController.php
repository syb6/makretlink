<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmerReview;
use App\Models\Product;
use App\Models\ProductReview;

class ModerationController extends Controller
{
    public function index()
    {
        return view('admin.moderation', [
            'products' => Product::with(['farmer.user', 'category'])->latest()->take(20)->get(),
            'productReviews' => ProductReview::with(['product', 'customer.user'])->latest()->take(20)->get(),
            'farmerReviews' => FarmerReview::with(['farmer.user', 'customer.user'])->latest()->take(20)->get(),
        ]);
    }

    public function toggleProduct(Product $product)
    {
        $product->update(['status' => $product->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Product '.$product->status.'.');
    }

    public function toggleProductReview(ProductReview $review)
    {
        $review->update(['status' => $review->status === 'visible' ? 'hidden' : 'visible']);

        return back()->with('success', 'Review '.$review->status.'.');
    }

    public function toggleFarmerReview(FarmerReview $review)
    {
        $review->update(['status' => $review->status === 'visible' ? 'hidden' : 'visible']);

        return back()->with('success', 'Review '.$review->status.'.');
    }
}
