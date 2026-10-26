<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why a quote did not apply a coupon (`coupon.reason` in the pricing contract).
 */
enum CouponRejection: string
{
    /** No such code, or the coupon was deactivated. */
    case Unknown = 'unknown';

    case NotStarted = 'not_started';

    case Expired = 'expired';

    case MinSubtotalNotMet = 'min_subtotal_not_met';

    /** The customer reached the coupon's per-customer limit. Only checked when the quote has a customer. */
    case AlreadyRedeemed = 'already_redeemed';

    /** The coupon reached its total limit. */
    case Exhausted = 'exhausted';
}
