<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CouponRejection;

/**
 * Outcome of applying a coupon code to a quote. Valid when there is no rejection.
 */
final readonly class CouponCheck
{
    public function __construct(
        public string $code,
        public ?CouponRejection $rejection,
        public ?string $description,
    ) {}

    public function isValid(): bool
    {
        return ! $this->rejection instanceof CouponRejection;
    }
}
