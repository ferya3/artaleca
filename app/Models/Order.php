<?php

declare(strict_types=1);

namespace App\Models;

use App\Shop\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'number', 'token', 'status', 'payment_method', 'shipping_method',
        'customer_name', 'mobile', 'email', 'company', 'national_id', 'economic_code',
        'province', 'city', 'address', 'postal_code', 'notes',
        'subtotal', 'tax', 'tax_percent', 'shipping', 'total',
        'locale', 'ip_hash', 'staff_note', 'paid_at', 'cancelled_at',
    ];

    /** The token is the key to the customer's order page; never serialise it. */
    protected $hidden = ['token', 'ip_hash'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'tax' => 'integer',
            'tax_percent' => 'integer',
            'shipping' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function successfulPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', Payment::PAID)->latestOfMany();
    }

    /** The admin's route key is the number staff read; the public one is the token. */
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereIn('status', [OrderStatus::Paid, OrderStatus::AwaitingTransfer]);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /** Can the customer still pay this order online from its page? */
    public function canPayOnline(): bool
    {
        return $this->status === OrderStatus::Pending && $this->payment_method === 'online';
    }

    /**
     * `AL-` and seven capital letters.
     *
     * Letters only, and not for looks: the Persian pages turn every Latin digit
     * into a Persian one, so a number with digits in it reads AL-K۴YMJ۸J in the
     * page title and the SMS and AL-K4YMJ8J in the panel — two spellings of
     * one order. Without B, I, L, O, S or Z, nothing in it can be mistaken for
     * a digit either, so a number read down a phone line is the number typed
     * into the search box. 20⁷ is about 1.3 billion; collisions are retried.
     */
    public static function newNumber(): string
    {
        $alphabet = 'ACDEFGHJKMNPQRTUVWXY';

        do {
            $code = '';
            for ($i = 0; $i < 7; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $number = 'AL-'.$code;
        } while (self::query()->where('number', $number)->exists());

        return $number;
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }
}
