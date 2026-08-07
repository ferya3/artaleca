<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use HasFactory;

    public const TYPES = ['contact', 'quote'];

    public const STATUSES = ['new', 'read', 'replied', 'spam'];

    protected $fillable = [
        'type', 'name', 'company', 'email', 'phone', 'country_code',
        'subject', 'message', 'product_id', 'quantity', 'delivery_terms',
        'locale', 'ip_hash', 'user_agent', 'referer',
        'status', 'internal_note', 'handled_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeUnhandled(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    /**
     * Stable, non-reversible fingerprint of the sender's address, salted with
     * APP_KEY so it cannot be brute-forced back to an IP from the DB alone.
     */
    public static function hashIp(?string $ip): ?string
    {
        return $ip === null ? null : hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
