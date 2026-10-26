<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CouponType;
use Carbon\CarbonImmutable;

final readonly class NewCouponData
{
    public function __construct(
        public string $code,
        public CouponType $type,
        public int $value,
        public ?string $description = null,
        public int $minSubtotal = 0,
        public ?CarbonImmutable $startsAt = null,
        public ?CarbonImmutable $endsAt = null,
    ) {}
}
