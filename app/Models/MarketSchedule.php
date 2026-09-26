<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\MarketScheduleFactory> */
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
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
            'is_closed' => 'boolean',
        ];
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
