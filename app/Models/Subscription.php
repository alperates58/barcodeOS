<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';

    public const PAID_ACCESS_ELIGIBLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_TRIALING,
    ];

    public const KNOWN_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_TRIALING,
        self::STATUS_PAST_DUE,
        self::STATUS_UNPAID,
        self::STATUS_CANCELED,
        self::STATUS_EXPIRED,
        self::STATUS_INCOMPLETE,
        self::STATUS_INCOMPLETE_EXPIRED,
    ];

    public const PAID_PLAN_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_TRIALING,
    ];

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'provider',
        'provider_customer_id',
        'provider_subscription_id',
        'billing_cycle',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isPaidAccessEligible(): bool
    {
        return in_array($this->status, self::PAID_ACCESS_ELIGIBLE_STATUSES, true);
    }

    public function scopeActiveOrTrialing(Builder $query): Builder
    {
        return $query->whereIn('status', self::PAID_ACCESS_ELIGIBLE_STATUSES);
    }

    public function scopePaidPlan(Builder $query): Builder
    {
        return $query->activeOrTrialing();
    }

    public function scopeWithinCurrentPeriod(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('current_period_starts_at')
                    ->orWhere('current_period_starts_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('current_period_ends_at')
                    ->orWhere('current_period_ends_at', '>=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('cancelled_at')
                    ->orWhere('cancelled_at', '>', $now);
            });
    }

    public static function paidPlanStatuses(): array
    {
        return self::PAID_ACCESS_ELIGIBLE_STATUSES;
    }

    public static function knownStatuses(): array
    {
        return self::KNOWN_STATUSES;
    }
}
