<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Shop\Shop;
use App\Support\Sms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * An order, by SMS — to the sales manager, or to the customer.
 *
 * The same rules as the enquiry alert (see SendSmsAlert): queued, short, no
 * link, a panel that is not configured dropped once rather than retried, one
 * that refuses the message retried.
 *
 * The customer's message is in the language they ordered in, which is why the
 * locale is switched for the length of `text()` and back.
 */
class SendOrderSms implements ShouldQueue
{
    use Queueable;

    public const STAFF = 'staff';

    public const CUSTOMER = 'customer';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    /** @param  'placed'|'paid'|'shipped'  $event */
    public function __construct(public int $orderId, public string $recipient, public string $event) {}

    public function handle(): void
    {
        if (! Sms::enabled() || Sms::configurationError()) {
            return;
        }

        $order = Order::find($this->orderId);

        if (! $order) {
            return;
        }

        $mobile = $this->recipient === self::STAFF ? Sms::salesMobile() : $order->mobile;

        if ($mobile === '') {
            return;
        }

        [$status, $detail] = Sms::send($mobile, $this->text($order));

        if ($status === Sms::STATUS_FAILED) {
            // The detail, never the text: it carries the customer's name.
            Log::warning('Order SMS not delivered', ['order' => $order->number, 'to' => $this->recipient, 'detail' => $detail]);

            throw new \RuntimeException('The SMS panel refused the order message: '.$detail);
        }
    }

    public function text(Order $order): string
    {
        if ($this->recipient === self::STAFF) {
            return implode("\n", array_filter([
                __('admin.orders.sms.'.$this->event, ['number' => $order->number], 'fa'),
                mb_substr($order->customer_name, 0, 40),
                $order->mobile,
                $order->city.' — '.Shop::money($order->total),
            ]));
        }

        $previous = App::getLocale();
        App::setLocale($order->locale);

        try {
            return __('shop.sms.'.$this->event, [
                'brand' => content('seo.brand_name'),
                'number' => $order->number,
                'total' => Shop::money($order->total),
            ]);
        } finally {
            App::setLocale($previous);
        }
    }
}
