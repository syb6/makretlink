<?php

namespace App\Models;

use Database\Factories\MarketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Services\ImageLibrary;

class Market extends Model
{
    /** @use HasFactory<MarketFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'map_provider',
        'description',
        'image',
        'status',
    ];

    /**
     * URL to the market's picture straight from its `image` column
     * (a path inside public/images/placeholders/market-images/),
     * falling back to the generic market placeholder.
     */
    public function getImageUrlAttribute(): string
    {
        return ImageLibrary::url('market', $this->image)
            ?? asset('images/placeholders/market.png');
    }

    /**
     * Scope to markets within a radius (km) of the given coordinates,
     * using the Haversine formula. The distance is selected as
     * `distance_km` so callers can read it off each model.
     *
     * Pure SQL — works on MySQL (production). Tests running on SQLite
     * register the trig functions as PDO custom functions (see
     * AppServiceProvider::boot).
     */
    public function scopeNearby($query, float $latitude, float $longitude, float $radiusKm = 10)
    {
        $earthRadiusKm = 6371;

        $haversine = <<<SQL
            ({$earthRadiusKm} * 2 * ASIN(SQRT(
                POW(SIN(RADIANS(latitude - ?) / 2), 2)
                + COS(RADIANS(?)) * COS(RADIANS(latitude))
                * POW(SIN(RADIANS(longitude - ?) / 2), 2)
            )))
        SQL;

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'active')
            ->select('*')
            ->selectRaw("{$haversine} AS distance_km", [$latitude, $latitude, $longitude])
            // CAST(... AS DECIMAL) protects the comparison on SQLite (test
            // env): PDO binds numbers as TEXT there, and SQLite ranks any
            // TEXT above any REAL, which would make every market "within"
            // radius. DECIMAL(10,4) is valid on MariaDB/MySQL/SQLite alike.
            ->whereRaw("{$haversine} <= CAST(? AS DECIMAL(10, 4))", [$latitude, $latitude, $longitude, $radiusKm])
            ->orderBy('distance_km');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MarketSchedule::class);
    }

    /**
     * Days this market actually trades, derived from its schedules so every
     * label on the site stays consistent with the data (no hardcoded
     * "Open Saturday" strings that drift out of sync).
     *
     * @return \Illuminate\Support\Collection<int, MarketSchedule>
     */
    public function openDays(): \Illuminate\Support\Collection
    {
        return $this->schedules
            ->filter(fn (MarketSchedule $s) => ! $s->is_closed)
            ->sortBy('day_of_week')
            ->values();
    }

    /**
     * Human label for cards: "Saturdays" when one trading day,
     * "Sat & Sun" when two, "Multiple days" beyond that.
     */
    public function openDaysLabel(): string
    {
        $days = $this->openDays();

        return match (true) {
            $days->isEmpty() => 'See schedule',
            $days->count() === 1 => 'Open '.\App\Models\MarketSchedule::DAYS[$days->first()->day_of_week],
            $days->count() === 2 => 'Open '.collect($days)
                ->map(fn ($s) => substr(\App\Models\MarketSchedule::DAYS[$s->day_of_week], 0, 3))
                ->implode(' & '),
            default => 'Open multiple days',
        };
    }

    public function farmerMarkets(): HasMany
    {
        return $this->hasMany(FarmerMarket::class);
    }

    public function favoriteMarkets(): HasMany
    {
        return $this->hasMany(FavoriteMarket::class);
    }
}
