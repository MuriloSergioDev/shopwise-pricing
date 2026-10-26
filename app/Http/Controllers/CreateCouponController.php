<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCoupon;
use App\Http\Requests\CreateCouponRequest;
use App\Http\Resources\CouponResource;
use Illuminate\Http\JsonResponse;

final class CreateCouponController
{
    public function __invoke(CreateCouponRequest $request, CreateCoupon $createCoupon): JsonResponse
    {
        return CouponResource::make($createCoupon->handle($request->toData()))->response()->setStatusCode(201);
    }
}
