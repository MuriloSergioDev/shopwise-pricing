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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['code', 'description', 'type', 'value', 'min_subtotal', 'starts_at', 'ends_at', 'active'])]
final class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    use HasUlids;

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
     * Why this coupon cannot be applied to the subtotal right now, or null when it can.
     */
    public function rejectionFor(Money $subtotal, CarbonInterface $now): ?CouponRejection
    {
        return match (true) {
            ! $this->active => CouponRejection::Unknown,
            $this->starts_at !== null && $now->lessThan($this->starts_at) => CouponRejection::NotStarted,
            $this->ends_at !== null && $now->greaterThanOrEqualTo($this->ends_at) => CouponRejection::Expired,
            $subtotal->getMinorAmount()->toInt() < $this->min_subtotal => CouponRejection::MinSubtotalNotMet,
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
        ];
    }
}
