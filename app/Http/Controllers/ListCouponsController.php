<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListCouponsController
{
    /**
     * Every coupon, newest first (ULID ids sort by creation time).
     */
    public function __invoke(): AnonymousResourceCollection
    {
        return CouponResource::collection(Coupon::query()->orderByDesc('id')->get());
    }
}
