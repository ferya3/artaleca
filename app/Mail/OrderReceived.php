<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A new or newly paid order, to the sales inbox. */
class OrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[%s] %s — %s', $this->event === 'paid' ? 'Order paid' : 'New order', $this->order->number, $this->order->customer_name),
            replyTo: filled($this->order->email) ? [new Address($this->order->email, $this->order->customer_name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-received',
            with: ['order' => $this->order->loadMissing('items'), 'event' => $this->event],
        );
    }
}
