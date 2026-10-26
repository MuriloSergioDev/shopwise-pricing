<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * Shape defined in shopwise-contracts/openapi/pricing.yaml (components.schemas.Coupon).
 *
 * @mixin Coupon
 */
final class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            // The contract types description as a string (not nullable): left out when there is none.
            'description' => $this->description ?? new MissingValue,
            'type' => $this->type->value,
            'value' => $this->value,
            'minSubtotal' => $this->min_subtotal,
            'startsAt' => $this->starts_at?->toIso8601ZuluString(),
            'endsAt' => $this->ends_at?->toIso8601ZuluString(),
            'active' => $this->active,
            'createdAt' => $this->created_at->toIso8601ZuluString(),
        ];
    }
}
