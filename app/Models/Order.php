<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\OrderStatusUpdate;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    public const STATUSES = [
        'placed',
        'accepted',
        'declined',
        'ready_for_pickup',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'order_number',
        'customer_id',
        'farmer_market_id',
        'pickup_slot_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'farmer_name',
        'market_name',
        'pickup_date',
        'pickup_start_time',
        'pickup_end_time',
        'status',
        'total_amount',
        'customer_note',
        'placed_at',
        'accepted_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'pickup_start_time' => 'datetime:H:i',
            'pickup_end_time' => 'datetime:H:i',
            'placed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function farmerMarket(): BelongsTo
    {
        return $this->belongsTo(FarmerMarket::class);
    }

    public function pickupSlot(): BelongsTo
    {
        return $this->belongsTo(PickupSlot::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function farmerReviews(): HasMany
    {
        return $this->hasMany(FarmerReview::class);
    }

    public function markStatus(string $status, ?string $note = null, ?User $changedBy = null): void
    {
        $timestamps = [
            'accepted' => 'accepted_at',
            'ready_for_pickup' => 'ready_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        $this->update([
            'status' => $status,
            $timestamps[$status] ?? null => now(),
        ]);

        $this->statusHistories()->create([
            'status' => $status,
            'changed_by' => $changedBy?->id,
            'note' => $note,
            'changed_at' => now(),
        ]);

        $this->notifyParties($status, $note, $changedBy);
    }

    /**
     * SRS: "Notifications: Email or in-app alerts for order updates and pickup readiness."
     * In-app via the notifications table; email via the configured mailer.
     *
     * Mail failures are contained per-recipient: a rejected email (bad
     * address, provider hiccup) must never roll back a placed order or a
     * status change — the in-app notification is the source of truth.
     */
    public function notifyParties(string $status, ?string $note, ?User $actor): void
    {
        // Reload fresh copies so mail rendering sees the new status.
        $order = $this->fresh() ?? $this;

        if ($order->customer?->user && ($actor?->id !== $order->customer->user->id)) {
            $this->notifySafely($order->customer->user, new OrderStatusUpdate($order, $status, $note, 'customer'));
        }

        $farmerUser = $order->farmerMarket?->farmer?->user;

        if ($farmerUser && ($actor?->id !== $farmerUser->id)) {
            $this->notifySafely($farmerUser, new OrderStatusUpdate($order, $status, $note, 'farmer'));
        }
    }

    /** Send a notification, containing any transport failure to the log. */
    private function notifySafely(User $user, OrderStatusUpdate $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            report($e); // logged with full context; app flow continues
        }
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }
}
