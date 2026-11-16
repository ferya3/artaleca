<?php

declare(strict_types=1);

namespace App\Shop;

use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The basket, in the session: variant id → quantity, nothing else.
 *
 * Prices are never stored here. Every read goes back to the database, so a
 * price changed in the panel is the price the customer pays, and a variant
 * taken off sale drops out of the basket instead of being sold at a price
 * nobody is offering any more.
 */
final class Cart
{
    private const KEY = 'shop.cart';

    /** A basket is a few lines of building material, not a warehouse. */
    public const MAX_LINES = 20;

    public function __construct(private readonly Session $session) {}

    /** @return array<int, int> */
    public function raw(): array
    {
        $raw = $this->session->get(self::KEY, []);

        return is_array($raw) ? array_filter($raw, fn ($q, $id) => is_int($id) && is_int($q) && $q > 0, ARRAY_FILTER_USE_BOTH) : [];
    }

    public function add(ProductVariant $variant, int $quantity): void
    {
        $raw = $this->raw();

        if (! isset($raw[$variant->id]) && count($raw) >= self::MAX_LINES) {
            return;
        }

        $raw[$variant->id] = $variant->clampQuantity(($raw[$variant->id] ?? 0) + $quantity);
        $this->session->put(self::KEY, $raw);
    }

    public function set(ProductVariant $variant, int $quantity): void
    {
        $raw = $this->raw();

        if (isset($raw[$variant->id])) {
            $raw[$variant->id] = $variant->clampQuantity($quantity);
            $this->session->put(self::KEY, $raw);
        }
    }

    public function remove(int $variantId): void
    {
        $raw = $this->raw();
        unset($raw[$variantId]);
        $this->session->put(self::KEY, $raw);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    /**
     * The basket as it can be sold right now. Lines whose variant is gone or
     * off sale are dropped, and quantities that no longer fit — the stock went
     * down, the minimum went up — are corrected, all written back so the page
     * and the checkout agree.
     *
     * @return Collection<int, array{variant: ProductVariant, quantity: int, line_total: int}>
     */
    public function lines(): Collection
    {
        $raw = $this->raw();

        if ($raw === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->purchasable()
            ->with('product')
            ->whereIn('id', array_keys($raw))
            ->get()
            ->keyBy('id');

        $clean = [];
        $lines = collect();

        foreach ($raw as $id => $quantity) {
            $variant = $variants->get($id);

            if (! $variant || ! $variant->inStock()) {
                continue;
            }

            $quantity = $variant->clampQuantity($quantity);
            $clean[$id] = $quantity;

            $lines->push([
                'variant' => $variant,
                'quantity' => $quantity,
                'line_total' => $variant->price * $quantity,
            ]);
        }

        if ($clean !== $raw) {
            $this->session->put(self::KEY, $clean);
        }

        return $lines;
    }

    /** Lines in the basket, for the header badge. Cheap: no query. */
    public function count(): int
    {
        return count($this->raw());
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('line_total');
    }
}
