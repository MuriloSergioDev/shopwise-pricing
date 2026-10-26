<?php

declare(strict_types=1);

namespace App\Data;

use Brick\Money\Money;

final readonly class Quote
{
    /**
     * @param  list<QuotedLine>  $lines
     */
    public function __construct(
        public array $lines,
        public Money $subtotal,
        public Money $discount,
        public Money $total,
        public ?CouponCheck $coupon,
    ) {}
}
