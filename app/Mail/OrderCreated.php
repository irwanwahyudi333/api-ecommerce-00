<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('order.created_subject', ['ORDER_TRACKING_NUMBER' => $this->order->tracking_number], $this->order->language),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.created',
            with: [
                'order' => $this->order,
                'trackingNumber' => $this->order->tracking_number,
                'total' => $this->order->paid_total,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
