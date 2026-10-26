<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/pricing.yaml (components.schemas.Price).
 *
 * @mixin Price
 */
final class PriceResource extends JsonResource
{
    /**
     * @return array{sku: string, amount: int, currency: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'sku' => $this->sku,
            'amount' => $this->price->getMinorAmount()->toInt(),
            'currency' => $this->price->getCurrency()->getCurrencyCode(),
        ];
    }
}
