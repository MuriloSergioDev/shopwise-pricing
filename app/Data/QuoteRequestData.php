<?php

declare(strict_types=1);

namespace App\Data;

final readonly class QuoteRequestData
{
    /**
     * @param  list<QuoteLineData>  $lines
     * @param  string|null  $customerId  JWT `sub` of the caller; null for an anonymous quote.
     */
    public function __construct(
        public array $lines,
        public ?string $couponCode = null,
        public ?string $customerId = null,
    ) {}
}
