<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\RedeemCoupon;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Records the coupon redemption of a completed checkout (ADR-0004). Checkouts without a coupon are ignored.
 */
final readonly class CheckoutCompletedHandler implements MessageHandler
{
    public function __construct(private RedeemCoupon $redeemCoupon) {}

    public function handle(Envelope $envelope): void
    {
        // Read the payload directly: the generated CompletedV1 types couponCode as a non-nullable string, but the
        // schema allows null (no coupon).
        /** @var array{checkoutId: string, orderId: string, customerId: string, couponCode: string|null, discount: int, currency: string, completedAt: string} $data */
        $data = $envelope->data;
        if ($data['couponCode'] === null) {
            return;
        }

        $this->redeemCoupon->handle($data);
    }
}
