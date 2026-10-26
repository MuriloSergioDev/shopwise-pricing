<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Price;
use App\Models\Sku;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Shopwise\Contracts\Events\Pricing\PriceChangedV1;
use Shopwise\Kit\Messaging\Outbox;

final readonly class SetPrice
{
    /**
     * Sets the unit price of a SKU known from catalog (404 otherwise). Records pricing.price-changed only when the
     * amount or currency actually changed.
     */
    public function handle(string $sku, Money $price): Price
    {
        return DB::transaction(function () use ($sku, $price): Price {
            // Lock the SKU row: it always exists, unlike the price row, so concurrent first prices serialize too.
            $known = Sku::query()->lockForUpdate()->findOrFail($sku);

            $current = Price::query()->find($known->sku);
            if ($current instanceof Price && $current->price->getCurrency()->isEqualTo($price->getCurrency()) && $current->price->isEqualTo($price)) {
                return $current;
            }

            $current ??= new Price(['sku' => $known->sku]);
            $current->price = $price;
            $current->save();

            Outbox::record(PriceChangedV1::TYPE, $current->sku, $current->changedEvent()->toArray(), PriceChangedV1::SCHEMA);

            return $current;
        });
    }
}
