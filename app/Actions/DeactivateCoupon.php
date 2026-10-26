<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Coupon;

final readonly class DeactivateCoupon
{
    /**
     * Deactivates the coupon (404 when the code is unknown). The row stays so its code is never reused.
     */
    public function handle(string $code): void
    {
        Coupon::query()->code($code)->firstOrFail()->update(['active' => false]);
    }
}
