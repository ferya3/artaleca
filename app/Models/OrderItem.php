<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an order, frozen as it was sold. The names are the full
 * translation maps of the day, so the line reads in whatever language the
 * order is later opened in, and survives the product being renamed or deleted.
 */
class OrderItem extends Model
{
    use HasTranslations;

    protected array $translatable = ['product_name', 'variant_name', 'unit'];

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'product_name', 'variant_name', 'unit',
        'unit_price', 'quantity', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'product_name' => 'array',
            'variant_name' => 'array',
            'unit' => 'array',
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
