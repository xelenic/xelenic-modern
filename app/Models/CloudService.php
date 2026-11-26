<?php

namespace App\Models;

use App\Enums\CloudProduct;
use App\Enums\CloudStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client's cloud service, resold from InterServer (see App\Cloud). It
 * starts as an order (requested); the team approves it, which buys it on
 * InterServer from the prepay balance (provisioning), and once InterServer
 * has set it up it's active, with its details mirrored in `remote`.
 */
#[Fillable([
    'product', 'status', 'name', 'plan', 'config', 'secret', 'currency', 'period', 'cost', 'price', 'monthly_price', 'quote',
    'interserver_uuid', 'interserver_invoices', 'remote', 'remote_status', 'next_due_at', 'synced_at', 'note', 'failure',
    'approved_by', 'approved_at', 'activated_at', 'cancel_requested_at', 'cancelled_at',
])]
class CloudService extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product' => CloudProduct::class,
            'status' => CloudStatus::class,
            'config' => 'array',
            // The client's chosen root password, until it's been used.
            'secret' => 'encrypted',
            'period' => 'integer',
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'monthly_price' => 'decimal:2',
            'quote' => 'array',
            'interserver_invoices' => 'array',
            'remote' => 'array',
            'next_due_at' => 'datetime',
            'synced_at' => 'datetime',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'cancel_requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param  Builder<CloudService>  $query
     */
    public function scopeWithStatus(Builder $query, CloudStatus ...$statuses): void
    {
        $query->whereIn('status', $statuses);
    }

    public function isActive(): bool
    {
        return $this->status === CloudStatus::Active;
    }

    /**
     * Ordered on InterServer: it has a service there to look after.
     */
    public function isOrdered(): bool
    {
        return $this->interserver_uuid !== null;
    }

    /**
     * How the price reads: "$12.00 / month", or "$66.00 every 6 months".
     */
    public function priceLabel(): string
    {
        $unit = $this->product->periodUnit();
        $every = $this->period === 1 ? "/ {$unit}" : "every {$this->period} {$unit}s";

        return '$'.number_format((float) $this->price, 2).' '.$every;
    }

    /**
     * The team's margin on it, per billing period.
     */
    public function margin(): float
    {
        return round((float) $this->price - (float) $this->cost, 2);
    }

    /**
     * A value from the InterServer details, by dot path.
     */
    public function remote(string $key, mixed $default = null): mixed
    {
        return data_get($this->remote, $key, $default);
    }
}
