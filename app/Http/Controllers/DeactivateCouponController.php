<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeactivateCoupon;
use Illuminate\Http\Response;

final class DeactivateCouponController
{
    public function __invoke(string $code, DeactivateCoupon $deactivateCoupon): Response
    {
        $deactivateCoupon->handle($code);

        return response()->noContent();
    }
}
