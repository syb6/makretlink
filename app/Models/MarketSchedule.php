<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\MarketScheduleFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketSchedule extends Model
{
    /** @use HasFactory<MarketScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'market_id',
        'day_of_week',
        'opening_time',
        'closing_time',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
        ];
    }

    protected function openingTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null
                ? null
                : Carbon::parse($value, 'UTC'),
            set: fn (mixed $value) => $value instanceof DateTimeInterface
                ? $value->format('H:i:s')
                : $value,
        );
    }

    protected function closingTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null
                ? null
                : Carbon::parse($value, 'UTC'),
            set: fn (mixed $value) => $value instanceof DateTimeInterface
                ? $value->format('H:i:s')
                : $value,
        );
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Unknown';
    }
}
