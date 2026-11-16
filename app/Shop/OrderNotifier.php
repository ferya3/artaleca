<?php

declare(strict_types=1);

namespace App\Shop;

use App\Jobs\SendOrderSms;
use App\Mail\OrderReceived;
use App\Models\Order;
use App\Support\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Who hears about an order, and when.
 *
 * Staff hear when there is something to do: a transfer order as soon as it is
 * placed (somebody has to watch the account for it), an online order once it
 * is paid (an unpaid one is a customer who closed the bank page). The customer
 * hears at the same two moments, and again when the truck leaves.
 *
 * Every channel is best-effort. The order is saved before any of this runs, so
 * a mail server or SMS panel that is down costs a notification, never an order
 * — the same rule the enquiry forms follow.
 */
final class OrderNotifier
{
    public static function placed(Order $order): void
    {
        if ($order->status === OrderStatus::AwaitingTransfer) {
            self::staff($order, 'placed');
            self::customer($order, 'placed');
        }
    }

    public static function paid(Order $order): void
    {
        self::staff($order, 'paid');
        self::customer($order, 'paid');
    }

    /** A transfer staff have seen arrive: the customer is told, staff already know. */
    public static function confirmedByStaff(Order $order): void
    {
        self::customer($order, 'paid');
    }

    public static function shipped(Order $order): void
    {
        self::customer($order, 'shipped');
    }

    private static function staff(Order $order, string $event): void
    {
        self::attempt(fn () => SendOrderSms::dispatch($order->id, SendOrderSms::STAFF, $event), $order, 'staff SMS');
        self::attempt(fn () => Mail::to(Contact::value('sales_email'))->send(new OrderReceived($order, $event)), $order, 'staff mail');
    }

    private static function customer(Order $order, string $event): void
    {
        self::attempt(fn () => SendOrderSms::dispatch($order->id, SendOrderSms::CUSTOMER, $event), $order, 'customer SMS');
    }

    private static function attempt(callable $send, Order $order, string $what): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::error("Order notification failed: {$what}", ['order' => $order->number, 'exception' => $e->getMessage()]);
        }
    }
}
