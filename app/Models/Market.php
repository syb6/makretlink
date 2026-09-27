<?php

namespace App\Models;

use Database\Factories\MarketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /** Market photo if uploaded, otherwise a fixed local fallback. */
    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : asset('images/placeholders/market.png');
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

    public function farmerMarkets(): HasMany
    {
        return $this->hasMany(FarmerMarket::class);
    }

    public function favoriteMarkets(): HasMany
    {
        return $this->hasMany(FavoriteMarket::class);
    }
}
