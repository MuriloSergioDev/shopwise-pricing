<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A completed checkout that used a coupon (recorded from `checkout.completed`). One per checkout.
 *
 * @property string $checkout_id
 * @property string $coupon_id
 * @property string $order_id
 * @property string $customer_id
 * @property int $discount
 * @property string $currency
 * @property Carbon $redeemed_at
 * @property-read Coupon $coupon
 */
#[Fillable(['checkout_id', 'coupon_id', 'order_id', 'customer_id', 'discount', 'currency', 'redeemed_at'])]
#[WithoutIncrementing]
#[WithoutTimestamps]
final class CouponRedemption extends Model
{
    protected $primaryKey = 'checkout_id';

    protected $keyType = 'string';

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    protected function casts(): array
    {
        return ['discount' => 'integer', 'redeemed_at' => 'datetime'];
    }
}
