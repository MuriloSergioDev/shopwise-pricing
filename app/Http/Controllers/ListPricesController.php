<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ListPricesRequest;
use App\Http\Resources\PriceResource;
use App\Models\Price;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListPricesController
{
    public function __invoke(ListPricesRequest $request): AnonymousResourceCollection
    {
        return PriceResource::collection(Price::query()->whereIn('sku', $request->skus())->orderBy('sku')->get());
    }
}
