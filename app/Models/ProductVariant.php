<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One way of buying a product: a bag, a cubic metre loose, a big bag.
 *
 * The product page describes the material; this is what goes in the cart. See
 * the shop migration for why the two are separate.
 */
class ProductVariant extends Model
{
    use HasFactory;
    use HasTranslations;

    protected array $translatable = ['name', 'unit'];

    protected $fillable = [
        'product_id', 'name', 'unit', 'sku', 'price',
        'min_quantity', 'max_quantity', 'step', 'stock',
        'position', 'is_active',
    ];

    /** What a new variant's form starts with: sold one at a time, on sale. */
    protected $attributes = [
        'min_quantity' => 1,
        'step' => 1,
        'is_active' => true,
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'unit' => 'array',
            'price' => 'integer',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'step' => 'integer',
            'stock' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * On sale: switched on, priced, and belonging to a product that is itself
     * published. A variant of a hidden product must not be buyable through a
     * cart link somebody saved.
     */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query
            ->where($query->qualifyColumn('is_active'), true)
            ->where($query->qualifyColumn('price'), '>', 0)
            ->whereHas('product', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('position'))
            ->orderBy($query->qualifyColumn('id'));
    }

    public function isPurchasable(): bool
    {
        return $this->is_active && $this->price > 0 && (bool) $this->product?->is_active;
    }

    /** The most one order may take: the ceiling, or what is left, whichever is lower. */
    public function available(): ?int
    {
        return match (true) {
            $this->stock === null => $this->max_quantity,
            $this->max_quantity === null => $this->stock,
            default => min($this->stock, $this->max_quantity),
        };
    }

    public function inStock(): bool
    {
        $available = $this->available();

        return $available === null || $available >= $this->min_quantity;
    }

    /**
     * The nearest quantity this variant can actually be sold in.
     *
     * Rounds up to the next step above the minimum and down to the ceiling,
     * so a cart can never hold "7 bags" of something sold in fives.
     */
    public function clampQuantity(int $quantity): int
    {
        $min = max(1, $this->min_quantity);
        $step = max(1, $this->step);

        $quantity = max($min, $quantity);
        $quantity = $min + (int) ceil(($quantity - $min) / $step) * $step;

        $ceiling = $this->available();

        if ($ceiling !== null && $quantity > $ceiling) {
            $quantity = $min + intdiv(max(0, $ceiling - $min), $step) * $step;
        }

        return $quantity;
    }

    /** Admin lists and selects: "LECA 4–10 — 50-litre bag". */
    public function label(): string
    {
        return trim(($this->product?->name ?? '').' — '.$this->name, ' —');
    }
}
