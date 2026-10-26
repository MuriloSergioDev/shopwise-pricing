<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records that a completed checkout used a coupon. Idempotent per checkout: a second call for the same checkoutId
 * changes nothing. Limits are not enforced here: the quote checked them before the saga started, and two concurrent
 * checkouts passing that check may exceed a limit (accepted in ADR-0004).
 */
final readonly class RedeemCoupon
{
    /**
     * @param  array{checkoutId: string, orderId: string, customerId: string, couponCode: string, discount: int, currency: string, completedAt: string}  $checkout
     */
    public function handle(array $checkout): void
    {
        DB::transaction(function () use ($checkout): void {
            // A coupon deactivated after the quote still counts: the customer got the discount.
            $coupon = Coupon::query()->code($checkout['couponCode'])->lockForUpdate()->first();
            if (! $coupon instanceof Coupon) {
                Log::warning('Completed checkout used an unknown coupon; not recorded', ['checkoutId' => $checkout['checkoutId'], 'couponCode' => $checkout['couponCode']]);

                return;
            }

            $inserted = CouponRedemption::query()->insertOrIgnore([
                'checkout_id' => $checkout['checkoutId'],
                'coupon_id' => $coupon->id,
                'order_id' => $checkout['orderId'],
                'customer_id' => $checkout['customerId'],
                'discount' => $checkout['discount'],
                'currency' => $checkout['currency'],
                'redeemed_at' => CarbonImmutable::parse($checkout['completedAt'])->utc(),
            ]);

            if ($inserted > 0) {
                $coupon->increment('redemption_count');
            }
        });
    }
}
