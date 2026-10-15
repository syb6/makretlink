<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\FarmerMarket;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Product;
use App\Models\WeeklyStock;
use Illuminate\Http\Request;

/**
 * Shared payload builders so every SPA data controller returns cards with a
 * consistent shape (public catalog, customer favorites, farmer/admin tables).
 */
trait BuildsApiPayloads
{
    private function stockCard(WeeklyStock $s): array
    {
        return [
            'id' => $s->id,
            'week_start' => $s->week_start?->toDateString(),
            'unit' => $s->product->unit,
            'price' => (float) $s->product->price,
            'quantity' => (float) $s->quantity,
            'available' => (float) $s->available_quantity,
            'status' => $s->status,
            'product' => array_merge(
                $s->product->only(['id', 'name', 'slug', 'image', 'price', 'unit']),
                ['category' => $s->product->category?->only(['id', 'name'])]
            ),
            'farmer' => $s->product->farmer ? $this->farmerCard($s->product->farmer) : null,
            'farmerMarket' => [
                'id' => $s->farmerMarket->id,
                'stall_name' => $s->farmerMarket->stall_name,
                'market' => $s->farmerMarket->market?->only(['id', 'name', 'address', 'image']),
            ],
        ];
    }

    private function productCard(Product $p): array
    {
        return array_merge($p->only(['id', 'name', 'slug', 'image', 'price', 'unit', 'status']), [
            'category' => $p->category?->only(['id', 'name']),
            'farmer' => $p->farmer ? $this->farmerCard($p->farmer) : null,
        ]);
    }

    private function marketCard(Market $m): array
    {
        return array_merge($m->only(['id', 'name', 'address', 'image', 'status', 'description']), [
            'latitude' => $m->latitude !== null ? (float) $m->latitude : null,
            'longitude' => $m->longitude !== null ? (float) $m->longitude : null,
            'farmers_count' => (int) ($m->farmers_count ?? 0),
            'schedules' => $m->relationLoaded('schedules')
                ? $m->schedules->map(fn ($s) => $s->only(['id', 'day_of_week', 'opening_time', 'closing_time', 'is_closed']))
                : [],
        ]);
    }

    private function farmerCard(FarmerProfile $f): array
    {
        return array_merge($f->only([
            'id', 'business_name', 'contact_person', 'description', 'profile_image', 'approval_status',
        ]), [
            'name' => $f->user?->name,
            'avatar' => $f->relationLoaded('user') ? $f->user?->avatarUrl() : null,
            'markets' => $f->relationLoaded('farmerMarkets')
                ? $f->farmerMarkets->filter(fn ($fm) => $fm->market?->status === 'active' && $fm->status === 'active')
                    ->map(fn (FarmerMarket $fm) => [
                        'id' => $fm->id,
                        'stall_name' => $fm->stall_name,
                        'stall_location' => $fm->stall_location,
                        'market' => $fm->market?->only(['id', 'name', 'address', 'image']),
                        'schedules' => $fm->relationLoaded('schedules')
                            ? $fm->schedules->where('is_active', true)->values()
                                ->map(fn ($s) => $s->only(['id', 'day_of_week', 'start_time', 'end_time']))
                            : [],
                    ])->values()
                : [],
        ]);
    }

    /** Paginate an Eloquent builder -> compact meta, no Laravel paginator shape. */
    private function paginate($query, Request $request, int $perPage): array
    {
        $page = max(1, (int) $request->query('page', '1'));
        $total = (clone $query)->toBase()->getCountForPagination();
        $rows = $query->forPage($page, $perPage)->get();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }
}
