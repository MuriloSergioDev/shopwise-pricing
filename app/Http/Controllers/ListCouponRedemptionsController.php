<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ListCouponRedemptionsRequest;
use App\Http\Resources\CouponRedemptionResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;

final class ListCouponRedemptionsController
{
    /**
     * Completed checkouts that used the coupon, newest first. Deactivated coupons still list theirs; only an unknown
     * code is 404.
     */
    public function __invoke(ListCouponRedemptionsRequest $request, string $code): JsonResponse
    {
        $coupon = Coupon::query()->code($code)->firstOrFail();
        $perPage = $request->integer('perPage', 25);

        $page = $coupon->redemptions()
            ->orderByDesc('redeemed_at')
            ->orderByDesc('checkout_id')
            ->paginate(perPage: $perPage, page: $request->integer('page', 1));

        return response()->json([
            'data' => CouponRedemptionResource::collection($page->items()),
            'meta' => [
                'page' => $page->currentPage(),
                'perPage' => $page->perPage(),
                'total' => $page->total(),
                'lastPage' => $page->lastPage(),
            ],
        ]);
    }
}
