<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\FarmerReview;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = FarmerReview::with(['customer.user', 'order'])
            ->where('farmer_id', Auth::user()->farmerProfile->id)
            ->latest()
            ->paginate(10);

        return view('farmer.reviews', ['reviews' => $reviews]);
    }
}
