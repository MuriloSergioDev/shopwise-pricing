<?php

declare(strict_types=1);

namespace App\Data;

final readonly class QuoteRequestData
{
    /**
     * @param  list<QuoteLineData>  $lines
     */
    public function __construct(
        public array $lines,
        public ?string $couponCode = null,
    ) {}
}
