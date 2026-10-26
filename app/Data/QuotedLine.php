<?php

declare(strict_types=1);

namespace App\Data;

use Brick\Money\Money;

/**
 * A priced line. Amounts are null when the SKU has no price.
 */
final readonly class QuotedLine
{
    public function __construct(
        public string $sku,
        public int $quantity,
        public ?Money $unitPrice,
        public ?Money $linePrice,
    ) {}
}
