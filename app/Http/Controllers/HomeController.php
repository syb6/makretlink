<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Product;
use App\Models\WeeklyStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $announcements = Announcement::published()->latest('published_at')->take(3)->get();

        $featuredStocks = WeeklyStock::with([
            'product.category',
            'product.farmer.user',
            'farmerMarket.market',
        ])
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            // Only current or upcoming weeks; past stock must not resurface after the week passes.
            ->where('week_start', '>=', now()->startOfWeek(\Illuminate\Support\Carbon::SUNDAY)->toDateString())
            // Only products of approved farmers sold at active stalls in active markets.
            ->whereHas('product', fn ($p) => $p
                ->where('status', 'active')
                ->whereHas('farmer', fn ($f) => $f->where('approval_status', 'approved')))
            ->whereHas('farmerMarket', fn ($fm) => $fm
                ->where('status', 'active')
                ->whereHas('market', fn ($m) => $m->where('status', 'active')))
            ->inRandomOrder()
            ->take(8)
            ->get();

        return view('home', [
            'announcements' => $announcements,
            'featuredStocks' => $featuredStocks,
            'marketCount' => Market::where('status', 'active')->count(),
            'farmerCount' => FarmerProfile::where('approval_status', 'approved')->count(),
            'productCount' => Product::where('status', 'active')->count(),
            'categories' => Category::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    /**
     * Lightweight rule-based assistant used by the chat widget.
     * Answers FAQs about markets, farmers, pickup and searches live stock.
     */
    public function assistant(Request $request): JsonResponse
    {
        $message = trim((string) $request->input('message'));
        $reply = 'Sorry, I did not understand that. Try asking about "market timings", "where can I buy tomatoes" or "how do pre-orders work?"';
        $links = [];

        if ($message === '') {
            return response()->json(['reply' => 'Hi! Ask me about markets, timings, products or how ordering works.']);
        }

        $q = strtolower($message);

        // Product search intent
        $stopwords = ['where', 'can', 'i', 'buy', 'find', 'get', 'a', 'an', 'the', 'is', 'there', 'any', 'do', 'you', 'have', 'sell', 'selling', 'shop', 'for', 'me', 'please', 'want', 'to', 'how', 'about', 'in', 'at', 'market', 'markets'];
        $words = collect(preg_split('/[^a-z0-9]+/', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->reject(fn ($w) => in_array($w, $stopwords, true))
            ->values();

        if ($words->isNotEmpty() && preg_match('/buy|find|looking|available|stock|have|sell/i', $q)) {
            $stocks = WeeklyStock::query()
                ->where('status', 'available')
                ->where('available_quantity', '>', 0)
                ->whereHas('product', fn ($p) => $p->where('status', 'active')->where(function ($sub) use ($words) {
                    foreach ($words as $w) {
                        $sub->orWhere('name', 'like', "%{$w}%");
                    }
                }))
                ->with(['product.farmer.user', 'farmerMarket.market'])
                ->limit(4)
                ->get();

            if ($stocks->isNotEmpty()) {
                $reply = 'Here is what I found for "' . $words->implode(', ') . '":';
                $links = $stocks->map(fn ($s) => [
                    'label' => sprintf('%s — %s (%s %s) at %s, stall "%s"',
                        $s->product->name,
                        '$' . number_format($s->product->price, 2),
                        $s->available_quantity,
                        $s->product->unit,
                        $s->farmerMarket->market->name,
                        $s->farmerMarket->stall_name ?? $s->product->farmer->business_name,
                    ),
                    'url' => route('products.show', $s->product),
                ])->all();
            } elseif (preg_match('/buy|find|looking|available|stock|have|sell/i', $q)) {
                $reply = 'I could not find that product in current stock. Try the products page with more filters.';
                $links = [['label' => 'Browse all products', 'url' => route('products.index')]];
            }
        }

        // Market timings intent
        if (preg_match('/timings?|open|hours|schedule/i', $q)) {
            $markets = Market::where('status', 'active')->with('schedules')->limit(3)->get();
            $reply = 'Market timings: '
                . $markets->map(function ($m) {
                    $days = $m->schedules->where('is_closed', false)
                        ->map(fn ($s) => \App\Models\MarketSchedule::DAYS[$s->day_of_week] ?? '')
                        ->implode(', ');

                    return $m->name . ' (' . ($days ?: 'schedule TBA') . ')';
                })->implode(' · ');
            $links[] = ['label' => 'See all markets & schedules', 'url' => route('markets.index')];
        }

        // Pre-order how-to intent
        if (preg_match('/pre.?order|order|pickup|pay|cancel/i', $q)) {
            $reply = 'Pre-orders are simple: add items to your cart, pick a market pickup slot, and pay in person at pickup. You can modify or cancel free of charge before the cutoff time shown on your order.';
            $links[] = ['label' => 'How it works', 'url' => route('about')];
        }

        // Farmers intent
        if (preg_match('/farmers?|stalls?|vendors?/i', $q)) {
            $reply = 'We have ' . FarmerProfile::where('approval_status', 'approved')->count() . ' approved farmers across our markets. You can browse each farmer\'s stall, operating days and weekly stock on their profile page.';
            $links[] = ['label' => 'Browse farmers', 'url' => route('farmers.index')];
        }

        // Greetings
        if (preg_match('/^(hi|hello|hey|good (morning|afternoon|evening))\b/i', $q)) {
            $reply = 'Hello! 👋 I can help you find products, check market timings or explain how pre-orders work. What are you looking for?';
            $links = [];
        }

        return response()->json(['reply' => $reply, 'links' => $links]);
    }
}
