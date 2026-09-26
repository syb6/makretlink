<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdate extends Notification
{
    use Queueable;

    /**
     * Human-readable copy per status, used by both the email and the in-app message.
     */
    public const MESSAGES = [
        'placed' => [
            'title' => 'Order placed',
            'body' => 'Your pre-order was placed successfully. The farmer will review it shortly.',
        ],
        'accepted' => [
            'title' => 'Order accepted',
            'body' => 'Great news — the farmer accepted your pre-order. See you at pickup!',
        ],
        'declined' => [
            'title' => 'Order declined',
            'body' => 'Unfortunately the farmer could not fulfil this order this week.',
        ],
        'ready_for_pickup' => [
            'title' => 'Ready for pickup',
            'body' => 'Your order is packed and ready. Collect it during your pickup window.',
        ],
        'completed' => [
            'title' => 'Order completed',
            'body' => 'Order picked up and paid. Thank you for supporting local farmers!',
        ],
        'cancelled' => [
            'title' => 'Order cancelled',
            'body' => 'This order was cancelled before the cutoff time.',
        ],
    ];

    public function __construct(
        public Order $order,
        public string $status,
        public ?string $note = null,
        public string $audience = 'customer', // 'customer' or 'farmer'
    ) {}

    /**
     * Notify instantly (synchronous) so the demo works without a queue worker.
     * Switch to ['mail', 'database'] via ShouldQueue for production volume.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $isCustomer = $this->audience === 'customer';

        $mail = (new MailMessage)
            ->subject($this->title().' — Order '.$this->order->order_number)
            ->greeting('Hello '.($isCustomer ? $this->order->customer_name : $this->order->farmer_name).'!')
            ->line($this->body())
            ->line('**Order:** '.$this->order->order_number)
            ->line('**Market:** '.$this->order->market_name)
            ->line('**Pickup:** '.$this->order->pickup_date->format('l, F j, Y').' · '
                .$this->order->pickup_start_time.' – '.$this->order->pickup_end_time);

        if ($this->note) {
            $mail->line('**Note:** '.$this->note);
        }

        return $mail
            ->line('Pre-orders are paid in person at pickup.')
            ->action($isCustomer ? 'View your order' : 'Open farmer dashboard', $this->targetUrl());
    }

    /**
     * Payload stored in the `notifications` table (JSON in `data` column).
     */
    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->status,
            'title' => $this->title(),
            'body' => $this->body(),
            'note' => $this->note,
            'audience' => $this->audience,
            'url' => $this->targetUrl(),
        ];
    }

    public function title(): string
    {
        return self::MESSAGES[$this->status]['title'] ?? 'Order update';
    }

    public function body(): string
    {
        return self::MESSAGES[$this->status]['body'] ?? 'The status of your order changed.';
    }

    private function targetUrl(): string
    {
        return $this->audience === 'customer'
            ? route('orders.show', $this->order)
            : route('farmer.orders.show', $this->order);
    }
}
