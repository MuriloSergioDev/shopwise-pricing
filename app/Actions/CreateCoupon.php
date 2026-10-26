<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\NewCouponData;
use App\Models\Coupon;

final readonly class CreateCoupon
{
    public function handle(NewCouponData $data): Coupon
    {
        return Coupon::query()->create([
            'code' => mb_strtoupper($data->code),
            'description' => $data->description,
            'type' => $data->type,
            'value' => $data->value,
            'min_subtotal' => $data->minSubtotal,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'active' => true,
        ]);
    }
}
