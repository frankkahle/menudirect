<?php

namespace App\Mail;

use App\Models\FoodOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public FoodOrder $order,
        public string $previousStatus
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->order->status) {
            FoodOrder::STATUS_CONFIRMED => 'Your Order is Confirmed!',
            FoodOrder::STATUS_PREPARING => 'Your Order is Being Prepared',
            FoodOrder::STATUS_READY => 'Your Order is Ready for ' . ($this->order->isPickup() ? 'Pickup' : 'Delivery') . '!',
            FoodOrder::STATUS_COMPLETED => 'Thank You for Your Order!',
            FoodOrder::STATUS_CANCELLED => 'Order Cancelled',
            default => 'Order Status Update',
        };

        return new Envelope(
            from: new Address('no-reply@menudirect.ca', 'MenuDirect'),
            subject: $subject . ' - #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status-update',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
