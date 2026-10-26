<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\Quote;
use App\Data\QuotedLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/pricing.yaml (components.schemas.Quote). Amounts in minor units.
 *
 * @property Quote $resource
 */
final class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quote = $this->resource;

        return [
            'currency' => $quote->subtotal->getCurrency()->getCurrencyCode(),
            'lines' => array_map(fn (QuotedLine $line): array => [
                'sku' => $line->sku,
                'quantity' => $line->quantity,
                'unitAmount' => $line->unitPrice?->getMinorAmount()->toInt(),
                'lineAmount' => $line->linePrice?->getMinorAmount()->toInt(),
            ], $quote->lines),
            'subtotal' => $quote->subtotal->getMinorAmount()->toInt(),
            'discount' => $quote->discount->getMinorAmount()->toInt(),
            'total' => $quote->total->getMinorAmount()->toInt(),
            'coupon' => $quote->coupon === null ? null : [
                'code' => $quote->coupon->code,
                'valid' => $quote->coupon->isValid(),
                'reason' => $quote->coupon->rejection?->value,
                'description' => $quote->coupon->description,
            ],
        ];
    }
}
