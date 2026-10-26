<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponRejection;
use App\Enums\CouponType;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonInterface;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $code
 * @property string|null $description
 * @property CouponType $type
 * @property int $value
 * @property int $min_subtotal
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $active
 * @property int|null $max_redemptions
 * @property int|null $max_redemptions_per_customer
 * @property int $redemption_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['code', 'description', 'type', 'value', 'min_subtotal', 'starts_at', 'ends_at', 'active', 'max_redemptions', 'max_redemptions_per_customer'])]
final class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * Matches the column default, so a coupon created in this request reports its count.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['redemption_count' => 0];

    /**
     * Codes are stored uppercase and matched case-insensitively.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function code(Builder $query, string $code): void
    {
        $query->where('code', mb_strtoupper($code));
    }

    /**
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Why this coupon cannot be applied to the subtotal right now, or null when it can. The per-customer limit is
     * only checked when the quote knows the customer (a request with an access token).
     */
    public function rejectionFor(Money $subtotal, CarbonInterface $now, ?string $customerId = null): ?CouponRejection
    {
        return match (true) {
            ! $this->active => CouponRejection::Unknown,
            $this->starts_at !== null && $now->lessThan($this->starts_at) => CouponRejection::NotStarted,
            $this->ends_at !== null && $now->greaterThanOrEqualTo($this->ends_at) => CouponRejection::Expired,
            $subtotal->getMinorAmount()->toInt() < $this->min_subtotal => CouponRejection::MinSubtotalNotMet,
            $customerId !== null && $this->max_redemptions_per_customer !== null
                && $this->redemptions()->where('customer_id', $customerId)->count() >= $this->max_redemptions_per_customer => CouponRejection::AlreadyRedeemed,
            $this->max_redemptions !== null && $this->redemption_count >= $this->max_redemptions => CouponRejection::Exhausted,
            default => null,
        };
    }

    /**
     * The discount on a subtotal: percentages round half-up to the cent, and a discount never exceeds the subtotal.
     */
    public function discountFor(Money $subtotal): Money
    {
        $discount = match ($this->type) {
            CouponType::Percent => $subtotal->multipliedBy($this->value)->dividedBy(100, RoundingMode::HalfUp),
            CouponType::Fixed => Money::ofMinor($this->value, $subtotal->getCurrency()),
        };

        return Money::min($discount, $subtotal);
    }

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_subtotal' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'active' => 'boolean',
            'max_redemptions' => 'integer',
            'max_redemptions_per_customer' => 'integer',
            'redemption_count' => 'integer',
        ];
    }
}
