<?php

declare(strict_types=1);

namespace App\Shop;

use App\Models\Setting;
use App\Shop\Payments\Gateway;
use App\Shop\Payments\Zarinpal;

/**
 * The shop's switches, all of them in Panel → Settings → Shop.
 *
 * It ships switched off. Turning it on is a decision somebody makes after the
 * prices are in — a shop that opens with no prices, or with last year's, is
 * worse than no shop — so the code that makes it possible can be deployed
 * long before that without anybody seeing it.
 */
final class Shop
{
    public const PAYMENT_ONLINE = 'online';

    public const PAYMENT_TRANSFER = 'transfer';

    /*
     * How bulk building material actually leaves a plant in Iran. A truck
     * booked through a freight office, the fare paid to the driver on
     * delivery (پس‌کرایه) — the fare depends on the city, the tonnage and the
     * week, so it is never part of the online total — or the customer's own
     * truck at the gate.
     */
    public const SHIPPING_FREIGHT = 'freight';

    public const SHIPPING_PICKUP = 'pickup';

    public static function enabled(): bool
    {
        return (bool) Setting::get('shop.enabled', false);
    }

    /** VAT, as a whole percentage. 0 means the prices shown are final. */
    public static function taxPercent(): int
    {
        return max(0, min(100, (int) Setting::get('shop.tax_percent', 0)));
    }

    public static function gateway(): ?Gateway
    {
        $merchant = trim((string) Setting::get('shop.zarinpal_merchant', ''));

        if ($merchant === '') {
            return null;
        }

        return new Zarinpal($merchant, (bool) Setting::get('shop.zarinpal_sandbox', false));
    }

    /**
     * The transfer details as HTML, one line each, with every card number and
     * IBAN isolated as left-to-right.
     *
     * Without that, a Persian line such as «شماره کارت: 6037-9911-2233-4455»
     * is laid out right to left and the bidi algorithm reorders the
     * hyphen-joined groups: the customer reads 4455-2233-9911-6037 and
     * transfers to a card that does not exist. Escaped first; the only markup
     * added is the fixed <bdi> tag.
     */
    public static function transferDetailsHtml(): string
    {
        return collect(preg_split('/\R/u', self::transferDetails()) ?: [])
            ->filter(fn (string $line) => trim($line) !== '')
            ->map(fn (string $line) => '<span class="block">'.preg_replace(
                '/[A-Z]{0,2}\d[\d\s\-]*\d/u',
                '<bdi dir="ltr">$0</bdi>',
                e(trim($line)),
            ).'</span>')
            ->implode('');
    }

    /** Card number, IBAN, account holder — whatever staff wrote for a transfer. */
    public static function transferDetails(): string
    {
        return trim((string) Setting::get('shop.transfer_details', ''));
    }

    /**
     * The ways to pay that are actually set up. Online needs a merchant id;
     * a transfer needs somewhere to transfer to. A shop with neither cannot
     * take an order, and says so rather than taking one nobody can pay.
     *
     * @return list<string>
     */
    public static function paymentMethods(): array
    {
        return array_values(array_filter([
            self::gateway() ? self::PAYMENT_ONLINE : null,
            self::transferDetails() !== '' ? self::PAYMENT_TRANSFER : null,
        ]));
    }

    /** @return list<string> */
    public static function shippingMethods(): array
    {
        return [self::SHIPPING_FREIGHT, self::SHIPPING_PICKUP];
    }

    /** Whole tomans, with the thousands separated and the unit after. */
    public static function money(int $toman): string
    {
        return number_format($toman).' '.__('shop.toman');
    }

    /**
     * @return array{subtotal: int, tax: int, tax_percent: int, shipping: int, total: int}
     */
    public static function totals(int $subtotal): array
    {
        $percent = self::taxPercent();
        // Rounded to the toman, half up — the same rule an invoice uses.
        $tax = intdiv($subtotal * $percent + 50, 100);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_percent' => $percent,
            'shipping' => 0,
            'total' => $subtotal + $tax,
        ];
    }

    /**
     * The provinces of Iran: stored value (the Persian name, which is what the
     * sales desk and the freight office read) → the name in this language.
     *
     * @return array<string, string>
     */
    public static function provinces(): array
    {
        $list = __('shop.provinces');

        return is_array($list) ? $list : [];
    }
}
