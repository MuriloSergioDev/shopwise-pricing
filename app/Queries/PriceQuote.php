<?php

declare(strict_types=1);

namespace App\Queries;

use App\Data\CouponCheck;
use App\Data\Quote;
use App\Data\QuotedLine;
use App\Data\QuoteLineData;
use App\Data\QuoteRequestData;
use App\Enums\CouponRejection;
use App\Models\Coupon;
use App\Models\Price;
use Brick\Money\Money;

/**
 * Prices lines and an optional coupon with current prices. Read-only: nothing is stored, no coupon is redeemed.
 */
final readonly class PriceQuote
{
    public function handle(QuoteRequestData $request): Quote
    {
        $currency = config()->string('pricing.currency');
        $skus = array_values(array_unique(array_map(fn (QuoteLineData $line): string => $line->sku, $request->lines)));
        $prices = Price::query()->whereIn('sku', $skus)->get()->keyBy('sku');

        $subtotal = Money::zero($currency);
        $lines = [];
        foreach ($request->lines as $line) {
            $unitPrice = $prices->get($line->sku)?->price;
            $linePrice = $unitPrice?->multipliedBy($line->quantity);
            if ($linePrice instanceof Money) {
                $subtotal = $subtotal->plus($linePrice);
            }

            $lines[] = new QuotedLine($line->sku, $line->quantity, $unitPrice, $linePrice);
        }

        $discount = Money::zero($currency);
        $check = null;
        if ($request->couponCode !== null) {
            $coupon = Coupon::query()->code($request->couponCode)->first();
            $rejection = $coupon instanceof Coupon ? $coupon->rejectionFor($subtotal, now()) : CouponRejection::Unknown;
            if ($coupon instanceof Coupon && ! $rejection instanceof CouponRejection) {
                $discount = $coupon->discountFor($subtotal);
            }

            // A deactivated coupon is "unknown": do not reveal its description either.
            $check = new CouponCheck(
                code: mb_strtoupper($request->couponCode),
                rejection: $rejection,
                description: $coupon instanceof Coupon && $rejection !== CouponRejection::Unknown ? $coupon->description : null,
            );
        }

        return new Quote($lines, $subtotal, $discount, $subtotal->minus($discount), $check);
    }
}
