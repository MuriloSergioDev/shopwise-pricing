<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CouponRedemption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/pricing.yaml (components.schemas.Redemption).
 *
 * @mixin CouponRedemption
 */
final class CouponRedemptionResource extends JsonResource
{
    /**
     * @return array{checkoutId: string, orderId: string, customerId: string, discount: int, currency: string, redeemedAt: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'checkoutId' => $this->checkout_id,
            'orderId' => $this->order_id,
            'customerId' => $this->customer_id,
            'discount' => $this->discount,
            'currency' => $this->currency,
            'redeemedAt' => $this->redeemed_at->toIso8601ZuluString(),
        ];
    }
}
