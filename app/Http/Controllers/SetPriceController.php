<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SetPrice;
use App\Http\Requests\SetPriceRequest;
use App\Http\Resources\PriceResource;
use Illuminate\Http\JsonResponse;

final class SetPriceController
{
    /**
     * Always 200 (the contract has no 201), even when this is the SKU's first price.
     */
    public function __invoke(SetPriceRequest $request, string $sku, SetPrice $setPrice): JsonResponse
    {
        return PriceResource::make($setPrice->handle($sku, $request->price()))->response()->setStatusCode(200);
    }
}
