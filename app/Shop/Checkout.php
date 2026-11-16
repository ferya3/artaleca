<?php

declare(strict_types=1);

namespace App\Shop;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Locales;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turning a basket into an order.
 *
 * One transaction: the variants are re-read under lock, their stock checked
 * and counted down, and the order written with a copy of every name and
 * price. If two customers race for the last ten cubic metres, one of them gets
 * an order and the other gets told — never both.
 */
final class Checkout
{
    /**
     * @param  Collection<int, array{variant: ProductVariant, quantity: int, line_total: int}>  $lines
     * @param  array<string, mixed>  $customer  validated checkout fields
     */
    public static function place(Collection $lines, array $customer, ?string $ipHash): Order
    {
        return DB::transaction(function () use ($lines, $customer, $ipHash) {
            $variants = ProductVariant::query()
                ->purchasable()
                ->with('product')
                ->whereIn('id', $lines->pluck('variant.id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $items = [];
            $subtotal = 0;

            foreach ($lines as $line) {
                $variant = $variants->get($line['variant']->id);
                $quantity = $line['quantity'];

                // Gone, repriced to nothing, or no longer enough of it: the
                // basket page re-reads and shows what is actually left.
                if (! $variant || ($variant->stock !== null && $variant->stock < $quantity)) {
                    throw ValidationException::withMessages(['cart' => __('shop.cart_changed')]);
                }

                if ($variant->stock !== null) {
                    $variant->decrement('stock', $quantity);
                }

                $lineTotal = $variant->price * $quantity;
                $subtotal += $lineTotal;

                $items[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->getTranslations('name'),
                    'variant_name' => $variant->getTranslations('name'),
                    'unit' => $variant->getTranslations('unit'),
                    'unit_price' => $variant->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $online = $customer['payment_method'] === Shop::PAYMENT_ONLINE;

            $order = Order::create([
                ...collect($customer)->only([
                    'payment_method', 'shipping_method', 'customer_name', 'mobile', 'email',
                    'company', 'national_id', 'economic_code', 'province', 'city', 'address',
                    'postal_code', 'notes',
                ])->all(),
                ...Shop::totals($subtotal),
                'number' => Order::newNumber(),
                'token' => Order::newToken(),
                'status' => $online ? OrderStatus::Pending : OrderStatus::AwaitingTransfer,
                'locale' => Locales::current(),
                'ip_hash' => $ipHash,
            ]);

            $order->items()->createMany($items);

            return $order;
        });
    }

    /**
     * Give a cancelled order's stock back. Only lines whose variant still
     * counts stock; a variant switched to "made to order" since has nothing
     * to give back to.
     */
    public static function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items()->with('variant')->get() as $item) {
                if ($item->variant && $item->variant->stock !== null) {
                    $item->variant->increment('stock', $item->quantity);
                }
            }
        });
    }
}
