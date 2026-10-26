<?php

declare(strict_types=1);

namespace App\Models;

use Brick\Money\Money;
use Database\Factories\PriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shopwise\Contracts\Events\Pricing\PriceChangedV1;
use Shopwise\Kit\Money\MoneyCast;

/**
 * Current unit price of a SKU.
 *
 * @property string $sku
 * @property Money $price
 * @property int $price_amount
 * @property string $price_currency
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['sku', 'price'])]
#[WithoutIncrementing]
final class Price extends Model
{
    /** @use HasFactory<PriceFactory> */
    use HasFactory;

    protected $primaryKey = 'sku';

    protected $keyType = 'string';

    public function changedEvent(): PriceChangedV1
    {
        return new PriceChangedV1(
            sku: $this->sku,
            amount: $this->price->getMinorAmount()->toInt(),
            currency: $this->price->getCurrency()->getCurrencyCode(),
            changedAt: $this->updated_at->toIso8601ZuluString(),
        );
    }

    protected function casts(): array
    {
        return ['price' => MoneyCast::class.':price', 'price_amount' => 'integer'];
    }
}
