<?php

declare(strict_types=1);

namespace App\Shop;

/**
 * Where an order is.
 *
 *   pending            placed, waiting for the online payment
 *   awaiting_transfer  placed to pay by bank transfer; staff confirm receipt
 *   paid               money received, not yet being prepared
 *   processing         being loaded / prepared
 *   shipped            left the plant
 *   delivered          received by the customer
 *   cancelled          will not be fulfilled; reserved stock is returned
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingTransfer = 'awaiting_transfer';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('shop.status.'.$this->value);
    }

    /** Tailwind classes for the little pill the status is shown in. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending, self::AwaitingTransfer => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Paid => 'bg-brand-50 text-brand-700 ring-brand-200',
            self::Processing, self::Shipped => 'bg-sky-50 text-sky-800 ring-sky-200',
            self::Delivered => 'bg-green-50 text-green-800 ring-green-200',
            self::Cancelled => 'bg-ink-100 text-ink-600 ring-ink-200',
        };
    }

    /** Statuses staff may move an order to by hand from this one. */
    public function next(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::AwaitingTransfer => [self::Paid, self::Cancelled],
            self::Paid => [self::Processing, self::Shipped, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Delivered, self::Cancelled], true);
    }
}
