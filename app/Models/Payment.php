<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One attempt to pay an order through a gateway. An order can have several —
 * a customer who closes the bank page and tries again — and is paid when one
 * of them is verified.
 */
class Payment extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    protected $fillable = [
        'order_id', 'gateway', 'amount', 'status',
        'authority', 'reference', 'card_pan', 'failure', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
