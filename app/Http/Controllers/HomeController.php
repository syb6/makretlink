<?php

namespace App\Http\Controllers;

use App\Services\AiAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function __construct(private AiAssistant $assistant)
    {
    }

    public function index()
    {
        $announcements = \App\Models\Announcement::published()->latest('published_at')->take(3)->get();

        $featuredStocks = \App\Models\WeeklyStock::with([
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
            'marketCount' => \App\Models\Market::where('status', 'active')->count(),
            'farmerCount' => \App\Models\FarmerProfile::where('approval_status', 'approved')->count(),
            'productCount' => \App\Models\Product::where('status', 'active')->count(),
            'categories' => \App\Models\Category::where('status', 'active')->orderBy('name')->get(),
            // Featured markets for the landing grid (SRS: browse markets from home)
            'markets' => \App\Models\Market::query()
                ->with(['schedules'])
                ->withCount(['farmerMarkets as farmers_count' => fn ($q) => $q->where('status', 'active')])
                ->where('status', 'active')
                ->orderBy('name')
                ->take(3)
                ->get(),
        ]);
    }

    /**
     * AI assistant endpoint (SRS 1.6 Customer feature 7).
     *
     * Stateful per browser session: keeps a short conversation history so
     * follow-ups work, and remembers which product links were already shown
     * so the widget never repeats itself. Groq-powered when GROQ_API_KEY is
     * set; rule-based fallback otherwise. Any unexpected failure degrades to
     * a graceful JSON reply — the widget never red-screens.
     */
    public function assistant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $history = Session::get('chat_history', []);
        $seenLinks = Session::get('chat_seen_links', []);

        try {
            $result = $this->assistant->ask($data['message'], $history, $seenLinks);
        } catch (\Throwable $e) {
            report($e);

            $result = [
                'reply' => 'Sorry — I hit a snag while thinking that over. Please try again in a moment.',
                'links' => [['label' => 'Browse all products', 'url' => route('products.index')]],
                'source' => 'error',
            ];
        }

        // Record the turn + the links we just showed (bounded memory).
        $history[] = ['role' => 'user', 'content' => $data['message']];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        Session::put('chat_history', array_slice($history, -8));

        $seenUrls = collect($seenLinks)->pluck('url')
            ->merge(collect($result['links'])->pluck('url'))
            ->unique()
            ->values()
            ->all();
        Session::put('chat_seen_links', array_slice($seenUrls, -40));

        return response()->json([
            'reply' => $result['reply'],
            'links' => $result['links'],
            'source' => $result['source'],
        ]);
    }
}
