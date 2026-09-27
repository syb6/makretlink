<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\MarketSchedule;
use App\Models\WeeklyStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * MarketLink AI assistant (SRS 1.6 — Customer feature 7).
 *
 * Design goals:
 *  1. Deep site knowledge — the model is taught how MarketLink actually works
 *     (roles, ordering flow, statuses, cutoffs, reviews, favorites).
 *  2. Conversation memory — the last few turns are replayed so follow-ups
 *     like "and what about milk?" or "how much is that?" work naturally.
 *  3. Useful, non-repeating links — one row per PRODUCT (stalls merged),
 *     deduplicated against what was already shown this conversation, and
 *     varied when the customer keeps browsing (new picks, not the same list).
 *
 * Fallback path: if no GROQ_API_KEY is configured or the API fails, the
 * rule-based logic answers FAQs so the widget never appears broken.
 */
class AiAssistant
{
    private const SEARCH_TRIGGERS = '/buy|find|looking|available|stock|have|sell|search|order|get|show|want|need/i';

    /** Small-talk / meta questions that need no product data in the prompt. */
    private const SMALLTALK = '/^(hi|hello|hey|yo|good (morning|afternoon|evening)|thanks?|thank you|ok|okay|great|cool|bye)\b[\s!.?]*$/i';

    /**
     * Everyday words -> catalog search words, so "bread" finds the Baked
     * Goods category and "milk" finds Dairy & Eggs even when no product is
     * literally named that.
     */
    private const SYNONYMS = [
        'bread' => ['baked', 'goods', 'sourdough', 'loaf', 'rye'],
        'baked' => ['baked', 'goods'],
        'bakery' => ['baked', 'goods'],
        'croissant' => ['baked', 'goods', 'croissants'],
        'pastry' => ['baked', 'goods', 'cinnamon', 'croissants'],
        'roll' => ['baked', 'goods', 'rolls'],
        'milk' => ['dairy', 'eggs'],
        'cheese' => ['dairy', 'eggs', 'cheddar'],
        'cheddar' => ['dairy', 'eggs'],
        'yogurt' => ['dairy', 'eggs'],
        'yoghurt' => ['dairy', 'eggs'],
        'egg' => ['dairy', 'eggs'],
        'eggs' => ['dairy', 'eggs'],
        'dairy' => ['dairy', 'eggs'],
        'veggie' => ['vegetables'],
        'veggies' => ['vegetables'],
        'vegetable' => ['vegetables'],
        'fruit' => ['fruits'],
        'berry' => ['fruits', 'blueberries'],
        'berries' => ['fruits', 'blueberries'],
        'apple' => ['fruits', 'apples'],
        'apples' => ['fruits', 'apples'],
        'greens' => ['herbs', 'greens', 'spinach'],
        'herb' => ['herbs', 'greens'],
        'herbs' => ['herbs', 'greens'],
        'honey' => ['honey', 'preserves'],
        'jam' => ['honey', 'preserves'],
        'preserve' => ['honey', 'preserves'],
        'preserves' => ['honey', 'preserves'],
        'sweet' => ['cinnamon', 'rolls', 'croissants'],
        'dessert' => ['cinnamon', 'rolls', 'croissants'],
        'carrot' => ['carrots'],
        'carrots' => ['carrots'],
        'tomato' => ['tomatoes'],
        'tomatoes' => ['tomatoes'],
        'peach' => ['peaches'],
        'peaches' => ['peaches'],
        'cherry' => ['cherries'],
        'cherries' => ['cherries'],
        'cucumber' => ['cucumbers'],
        'cucumbers' => ['cucumbers'],
        'pepper' => ['pepper'],
        'salad' => ['vegetables', 'spinach', 'tomatoes', 'cucumbers'],
    ];

    public function __construct(
        private ?string $apiKey = null,
        private ?string $model = null,
        private bool $enabled = true,
    ) {
        $this->apiKey = $this->apiKey ?? (string) config('services.groq.key');
        $this->model = $this->model ?? (string) config('services.groq.model', 'qwen/qwen3.8-27b');
        $this->enabled = config('services.groq.enabled', true) && $this->apiKey !== '';
    }

    /** True when Groq is configured and enabled. */
    public function isActive(): bool
    {
        return $this->enabled;
    }

    /**
     * Answer a customer question.
     *
     * @param  array<int, array{role: string, content: string}>  $history  Recent conversation turns (oldest first).
     * @param  array<int, array{label: string, url: string}>  $seenLinks  Links already shown to this customer.
     * @return array{reply: string, links: array<int, array{label: string, url: string}>, source: string}
     */
    public function ask(string $message, array $history = [], array $seenLinks = []): array
    {
        $message = trim($message);

        if ($message === '') {
            return ['reply' => 'Hi! Ask me things like "where can I buy tomatoes", "when is Green Valley open" or "how do pre-orders work?"', 'links' => [], 'source' => 'rules'];
        }

        // Small talk needs no DB grounding — answer directly and cheaply.
        if (preg_match(self::SMALLTALK, $message) && $history === []) {
            return [
                'reply' => 'Hello! 👋 I can help you find products, check market hours, or explain how pre-orders work. What are you looking for today?',
                'links' => [],
                'source' => 'rules',
            ];
        }

        $context = $this->buildContext($message, $seenLinks);

        if ($this->isActive()) {
            try {
                $answer = $this->askGroq($message, $context['digest'], $history);

                return [
                    'reply' => $answer,
                    'links' => $context['links'],
                    'source' => 'groq',
                ];
            } catch (\Throwable $e) {
                report($e); // logged; user still gets a rule-based answer
            }
        }

        return $this->ruleBasedAnswer($message, $context);
    }

    /* ---------------------------------------------------------------------
     | Groq (OpenAI-compatible chat completions)
     |--------------------------------------------------------------------*/

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function askGroq(string $message, string $digest, array $history): string
    {
        $system = <<<'TXT'
You are "Linky", the MarketLink Assistant. MarketLink is a farmers-market PRE-ORDER platform: farmers publish weekly stock, customers reserve it online, then collect and PAY IN PERSON at the market stall.

HOW MARKETLINK WORKS (use this to answer):
- Customers register as Customer or Farmer; farmers are approved by an admin before listing.
- Customers browse Products filtered by category, market, day and price; every product shows price, unit and quantity left.
- "Add to cart" reserves against real weekly stock; checkout groups items per stall.
- At checkout the customer picks a PICKUP SLOT (date + time window) offered by each farmer, with an order cutoff shown on the slot.
- Order statuses: placed -> accepted (farmer preparing) -> ready for pickup -> completed (handed over & paid). Orders can be cancelled or modified by the customer BEFORE the cutoff time; farmers may decline a placed order.
- Customers can reorder past orders, save favorite products/farmers/markets, and rate & review farmers and products AFTER an order is completed. Farmers can reply to reviews.
- There is NO online payment and NO delivery/handling/shipping — market pickup only.

ANSWER RULES:
- Answer ONLY from the LIVE PLATFORM DATA below plus the HOW IT WORKS knowledge. If a product is not in the data, say it's not in stock this week and suggest browsing the Products page — never invent products, farmers, prices or stock numbers.
- Use the conversation history: short follow-ups like "and milk?" mean the customer is still asking about the previous topic.
- Be genuinely helpful and concise: 1-3 short sentences, plain text, no markdown, no bullet lists.
- Vary your wording between answers — never repeat the same sentence structure every reply.
- If the customer asks what you can do, briefly list example questions (stock search, market hours, how ordering works).
TXT;

        $messages = [['role' => 'system', 'content' => $system]];

        // Replay recent turns so follow-ups make sense. History is already
        // trimmed by the controller; guard against runaway payloads anyway.
        foreach (array_slice($history, -6) as $turn) {
            $role = in_array($turn['role'] ?? '', ['user', 'assistant'], true) ? $turn['role'] : 'user';
            $messages[] = ['role' => $role, 'content' => mb_substr((string) $turn['content'], 0, 500)];
        }

        $messages[] = ['role' => 'user', 'content' => "LIVE PLATFORM DATA:\n{$digest}\n\nCUSTOMER QUESTION: {$message}"];

        $response = Http::withToken($this->apiKey)
            ->timeout(20)
            ->connectTimeout(8)
            ->acceptJson()
            ->post($this->endpoint('/chat/completions'), [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.6,
                'max_tokens' => 300,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Groq API error '.$response->status().': '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Groq returned an empty completion.');
        }

        // Some reasoning models occasionally emit <think> blocks in content;
        // strip them so the widget never shows chain-of-thought text.
        $content = trim(preg_replace('/<think>[\s\S]*?<\/think>/i', '', $content) ?? $content);

        if ($content === '') {
            throw new \RuntimeException('Groq completion contained only reasoning tokens.');
        }

        return $content;
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/').$path;
    }

    /* ---------------------------------------------------------------------
     | Grounding context built from the database
     |--------------------------------------------------------------------*/

    /**
     * Build the live-data digest and a useful, de-duplicated link list.
     *
     * @param  array<int, array{label: string, url: string}>  $seenLinks
     * @return array{digest: string, links: array<int, array{label: string, url: string}>}
     */
    private function buildContext(string $message, array $seenLinks = []): array
    {
        // Only approved farmers, active stalls in active markets, current-or-future
        // weekly stock — identical visibility rules as the public catalog.
        $stocks = WeeklyStock::query()
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->where('week_start', '>=', now()->startOfWeek(Carbon::SUNDAY)->toDateString())
            ->whereHas('product', fn ($p) => $p->where('status', 'active')
                ->whereHas('farmer', fn ($f) => $f->where('approval_status', 'approved')))
            ->whereHas('farmerMarket', fn ($fm) => $fm->where('status', 'active')
                ->whereHas('market', fn ($m) => $m->where('status', 'active')))
            ->with(['product.category', 'product.farmer', 'farmerMarket.market'])
            ->get();

        // ---- Merge rows per PRODUCT: one entry with aggregated availability ----
        $products = $stocks->groupBy(fn ($s) => $s->product_id)->map(function ($group) {
            /** @var WeeklyStock $first */
            $first = $group->first();
            $markets = $group->map(fn ($s) => $s->farmerMarket->market->name)->unique()->values();

            return (object) [
                'product' => $first->product,
                'quantity' => (float) $group->sum('available_quantity'),
                'unit' => $first->product->unit,
                'price' => (float) $first->product->price,
                'farmer' => $first->product->farmer->business_name,
                'markets' => $markets,
                'market_label' => $markets->count() === 1
                    ? $markets->first()
                    : $markets->first().' +'.($markets->count() - 1).' more market'.($markets->count() > 2 ? 's' : ''),
            ];
        })->values();

        $seenUrls = collect($seenLinks)->pluck('url')->all();
        $words = $this->keywords($message);

        // ---- Rank products: keyword matches first (name > category), then alphabetical ----
        $scored = $products->map(function ($entry) use ($words) {
            $name = mb_strtolower($entry->product->name);
            $cat = mb_strtolower($entry->product->category?->name ?? '');
            $score = 0;
            foreach ($words as $w) {
                if (preg_match('/\b'.preg_quote($w, '/').'\b/', $name)) {
                    $score += 5;
                } elseif ($w !== '' && str_contains($name, $w)) {
                    $score += 2;
                }
                if (preg_match('/\b'.preg_quote($w, '/').'\b/', $cat)) {
                    $score += 3;
                }
            }

            return [$entry, $score];
        })->sortByDesc(fn ($pair) => $pair[1])->values();

        $matches = $scored->filter(fn ($pair) => $pair[1] > 0);

        // A message is a product search when it has an explicit shopping verb
        // OR when its keywords actually hit products/categories in the catalog
        // (covers short follow-ups like "and milk?" or "any veggies?").
        $searching = $words->isNotEmpty()
            && (preg_match(self::SEARCH_TRIGGERS, $message) || $matches->isNotEmpty());

        if ($searching && $matches->isNotEmpty()) {
            $pool = $matches->map(fn ($pair) => $pair[0])->values();
        } else {
            // No clear product intent: offer a rotating sample of the catalog.
            $pool = $products->shuffle()->values();
        }

        // ---- Build links ----
        // Priority: (1) unseen products among the matches — fresh AND relevant;
        //           (2) if ALL matches were already shown, repeat the matches
        //               (that is what the customer asked for — better than
        //               serving random unrelated products);
        //           (3) when merely browsing, unseen catalog variety only.
        $makeLink = fn ($entry) => [
            'label' => sprintf('%s — Rs %s/%s, %s %s left (%s)',
                $entry->product->name,
                number_format($entry->price, 2),
                $entry->unit,
                rtrim(rtrim(number_format($entry->quantity, 2), '0'), '.'),
                $entry->unit,
                $entry->market_label),
            'url' => route('products.show', $entry->product),
        ];

        $links = [];
        $matchEntries = $matches->map(fn ($pair) => $pair[0])->values();

        if ($searching && $matchEntries->isNotEmpty()) {
            // (1) unseen matches first
            foreach ($matchEntries as $entry) {
                $url = route('products.show', $entry->product);
                if (in_array($url, $seenUrls, true)) {
                    continue;
                }
                $links[] = $makeLink($entry);
                if (count($links) >= 4) {
                    break;
                }
            }
            // (2) all relevant matches already shown? repeat them — the
            // customer asked for exactly these.
            if (count($links) < 4) {
                foreach ($matchEntries as $entry) {
                    $url = route('products.show', $entry->product);
                    if (in_array($url, array_column($links, 'url'), true)) {
                        continue;
                    }
                    $links[] = $makeLink($entry);
                    if (count($links) >= 4) {
                        break;
                    }
                }
            }
        } else {
            // (3) browsing: only products not already shown this conversation.
            foreach ($pool as $entry) {
                $url = route('products.show', $entry->product);
                if (in_array($url, $seenUrls, true)) {
                    continue;
                }
                $links[] = $makeLink($entry);
                if (count($links) >= 4) {
                    break;
                }
            }
        }

        // ---- Digest for the model (merged products = compact, no stall spam) ----
        $digestStocks = $searching && $matches->isNotEmpty()
            ? $matches->take(8)->map(fn ($pair) => $pair[0])
            : $products->sortByDesc('quantity')->take(8);

        $digest[] = 'PRODUCTS IN STOCK THIS WEEK (aggregated across stalls): '.$digestStocks
            ->map(fn ($e) => sprintf('%s (Rs %s/%s, %.10g %s total, by %s, at %s)',
                $e->product->name,
                number_format($e->price, 2),
                $e->unit,
                $e->quantity,
                $e->unit,
                $e->farmer,
                $e->markets->implode(' & ')))
            ->implode('; ');

        $markets = Market::where('status', 'active')->with('schedules')->orderBy('name')->get();
        $todayName = now()->format('l');
        $digest[] = 'MARKETS & HOURS: '.$markets->map(function ($m) {
            $open = $m->schedules->where('is_closed', false)
                ->sortBy('day_of_week')
                ->map(fn ($s) => (MarketSchedule::DAYS[$s->day_of_week] ?? '').' '.$s->opening_time?->format('g:i A').'-'.$s->closing_time?->format('g:i A'))
                ->implode(', ');

            return $m->name.' ('.($m->address ?: 'address TBA').')'.($open !== '' ? ': '.$open : ': schedule TBA');
        })->implode(' | ');
        $digest[] = "TODAY IS: {$todayName}.";

        $digest[] = 'APPROVED FARMERS: '.FarmerProfile::where('approval_status', 'approved')
            ->with('farmerMarkets.market')
            ->take(15)
            ->get()
            ->map(fn ($f) => $f->business_name
                .($f->farmerMarkets->isNotEmpty() ? ' at '.$f->farmerMarkets->map(fn ($fm) => $fm->market->name)->implode(', ') : ''))
            ->implode('; ');

        $digest[] = 'CATEGORIES: '.Category::where('status', 'active')->orderBy('name')->get()->pluck('name')->implode(', ');

        return [
            'digest' => implode("\n", array_filter($digest)),
            'links' => $links,
        ];
    }

    /**
     * Extract meaningful keywords from the message (stopwords removed),
     * expanded with catalog synonyms ("bread" -> baked goods, "milk" -> dairy).
     *
     * @return Collection<int, string>
     */
    private function keywords(string $message): Collection
    {
        $words = collect(preg_split('/[^a-z0-9]+/', strtolower($message), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->reject(fn ($w) => in_array($w, [
                'where', 'can', 'i', 'buy', 'find', 'get', 'a', 'an', 'the', 'is', 'are', 'there', 'any',
                'do', 'does', 'you', 'have', 'has', 'sell', 'selling', 'shop', 'for', 'me', 'please',
                'want', 'to', 'how', 'about', 'in', 'at', 'on', 'of', 'and', 'or', 'market', 'markets',
                'some', 'looking', 'available', 'what', 'which', 'when', 'who', 'much', 'many', 'still',
                'now', 'today', 'week', 'this', 'that', 'it', 'also', 'tell', 'show', 'need', 'order',
                'stock', 'left', 'more', 'other', 'else', 'got', 'am', 'pm', 'open', 'close', 'opens', 'closes',
            ], true))
            ->unique()
            ->values();

        // Expand with synonyms so everyday words hit the right categories.
        $expanded = $words->flatMap(fn ($w) => self::SYNONYMS[$w] ?? [])->values();

        return $words->merge($expanded)->unique()->values();
    }

    /* ---------------------------------------------------------------------
     | Rule-based fallback (no API key, or API failure)
     |--------------------------------------------------------------------*/

    /**
     * @param  array{digest: string, links: array<int, array{label: string, url: string}>}  $context
     * @return array{reply: string, links: array<int, array{label: string, url: string}>, source: string}
     */
    private function ruleBasedAnswer(string $message, array $context): array
    {
        $q = strtolower($message);

        if (preg_match(self::SMALLTALK, $q)) {
            return [
                'reply' => 'Happy to help! 🙂 Try "where can I buy tomatoes", "when is Green Valley open" or "how do pre-orders work?"',
                'links' => [],
                'source' => 'rules',
            ];
        }

        // FAQ intents are matched BEFORE the broad product-search trigger so
        // questions like "how do pre-orders work?" are not mistaken for a
        // product search by the word "order".
        if (preg_match('/timings?|open|hours|schedule\b|when is|when are/i', $q)) {
            $reply = 'MarketLink is pickup-only, so hours depend on each market. '.$this->marketsLine();

            return ['reply' => $reply, 'links' => [['label' => 'See all markets & schedules', 'url' => route('markets.index')]], 'source' => 'rules'];
        }

        if (preg_match('/farmers?|stalls?|vendors?/i', $q) && ! preg_match('/hour|open|schedule/i', $q)) {
            $count = FarmerProfile::where('approval_status', 'approved')->count();

            return [
                'reply' => "We have {$count} approved farmers across our markets. You can browse each farmer's stall, operating days and weekly stock on their profile page.",
                'links' => [['label' => 'Browse farmers', 'url' => route('farmers.index')]],
                'source' => 'rules',
            ];
        }

        if (preg_match('/how (do|does|to)|pre.?order|pickup|pay|cancel|modify|work|review|favorite/i', $q)) {
            return [
                'reply' => 'Pre-orders are simple: add items to your cart, pick a market pickup slot, and pay the farmer in person at pickup. You can cancel free of charge before the cutoff time shown on your order.',
                'links' => [['label' => 'How it works', 'url' => route('about')]],
                'source' => 'rules',
            ];
        }

        if (preg_match('/deliv|ship|courier/i', $q)) {
            return [
                'reply' => "MarketLink is pickup only — you collect your reserved items at the farmer's market stall. Delivery is out of scope by design.",
                'links' => [['label' => 'Find a market near you', 'url' => route('markets.index')]],
                'source' => 'rules',
            ];
        }

        if (preg_match(self::SEARCH_TRIGGERS, $q)) {
            if (! empty($context['links'])) {
                return ['reply' => 'Here is what I found in this week\'s stock:', 'links' => $context['links'], 'source' => 'rules'];
            }

            return [
                'reply' => 'I could not find that product in current stock. Try the products page with more filters.',
                'links' => [['label' => 'Browse all products', 'url' => route('products.index')]],
                'source' => 'rules',
            ];
        }

        return [
            'reply' => 'Sorry, I did not understand that. Try asking about "market timings", "where can I buy tomatoes" or "how do pre-orders work?"',
            'links' => $context['links'],
            'source' => 'rules',
        ];
    }

    private function marketsLine(): string
    {
        $line = Market::where('status', 'active')->with('schedules')->limit(4)->get()
            ->map(function ($m) {
                $days = $m->schedules->where('is_closed', false)
                    ->map(fn ($s) => MarketSchedule::DAYS[$s->day_of_week] ?? '')
                    ->implode(', ');

                return $m->name.' ('.($days ?: 'schedule TBA').')';
            })
            ->implode(' · ');

        return $line !== '' ? $line : 'No active markets listed yet.';
    }
}
