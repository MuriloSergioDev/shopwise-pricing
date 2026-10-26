<?php

declare(strict_types=1);

namespace App\Data;

/**
 * One line to price: a SKU and how many of it.
 */
final readonly class QuoteLineData
{
    public function __construct(
        public string $sku,
        public int $quantity,
    ) {}
}
