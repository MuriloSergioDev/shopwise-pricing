<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Price;
use Shopwise\Kit\Messaging\DispatchResult;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageDispatcher;

/**
 * A checkout.completed envelope with a fresh message id, so the transport dedup does not swallow it.
 *
 * @param  array<string, mixed>  $data
 */
function completedCheckout(array $data = []): Envelope
{
    $example = contractExample('checkout.completed.v1', $data);

    return Envelope::fromArray([...$example->toArray(), 'id' => (string) str()->ulid()]);
}

function dispatchCompleted(Envelope $envelope): DispatchResult
{
    return app(MessageDispatcher::class)->dispatch($envelope, 'checkout.completed');
}

beforeEach(function (): void {
    Price::factory()->forSku('TEE', 2900)->create();
});

it('records a redemption from checkout.completed exactly once', function (): void {
    $coupon = Coupon::factory()->percent(10)->create(['code' => 'WELCOME10']);
    $envelope = contractExample('checkout.completed.v1');

    expect(dispatchCompleted($envelope))->toBe(DispatchResult::Handled)
        ->and(dispatchCompleted($envelope))->toBe(DispatchResult::Duplicate);

    $redemption = CouponRedemption::query()->sole();
    expect($redemption->checkout_id)->toBe('01JBCDEFGHJKMNPQRSTVWXYZ40')
        ->and($redemption->coupon_id)->toBe($coupon->id)
        ->and($redemption->order_id)->toBe('01JBCDEFGHJKMNPQRSTVWXYZ44')
        ->and($redemption->customer_id)->toBe('01JBCDEFGHJKMNPQRSTVWXYZ11')
        ->and($redemption->discount)->toBe(580)
        ->and($redemption->currency)->toBe('USD')
        ->and($redemption->redeemed_at->toIso8601ZuluString())->toBe('2026-10-26T12:01:30Z')
        ->and($coupon->refresh()->redemption_count)->toBe(1);
});

it('records a checkout once even when checkout re-publishes it under a new message id', function (): void {
    $coupon = Coupon::factory()->create(['code' => 'WELCOME10']);

    dispatchCompleted(completedCheckout());
    expect(dispatchCompleted(completedCheckout()))->toBe(DispatchResult::Handled);

    expect(CouponRedemption::query()->count())->toBe(1)
        ->and($coupon->refresh()->redemption_count)->toBe(1);
});

it('ignores checkouts without a coupon or with an unknown one', function (): void {
    Coupon::factory()->create(['code' => 'WELCOME10']);

    expect(dispatchCompleted(completedCheckout(['couponCode' => null, 'discount' => 0])))->toBe(DispatchResult::Handled)
        ->and(dispatchCompleted(completedCheckout(['couponCode' => 'NOPE'])))->toBe(DispatchResult::Handled)
        ->and(CouponRedemption::query()->count())->toBe(0);
});

it('records a redemption of a coupon deactivated after the quote, matching the code case-insensitively', function (): void {
    $coupon = Coupon::factory()->inactive()->create(['code' => 'WELCOME10']);

    dispatchCompleted(completedCheckout(['couponCode' => 'welcome10']));

    expect($coupon->refresh()->redemption_count)->toBe(1);
});

it('rejects a coupon the customer already used up, but only when the quote has their token', function (): void {
    Coupon::factory()->limited(null, perCustomer: 1)->create(['code' => 'ONCE']);
    $quote = ['lines' => [['sku' => 'TEE', 'quantity' => 1]], 'couponCode' => 'ONCE'];

    $this->postJson('/api/quotes', $quote, bearer(['customer']))
        ->assertOk()
        ->assertJsonPath('data.coupon.valid', true)
        ->assertJsonPath('data.discount', 290);

    dispatchCompleted(completedCheckout(['couponCode' => 'ONCE', 'customerId' => '01JBCDEFGHJKMNPQRSTVWXYZ99']));

    $this->postJson('/api/quotes', $quote, bearer(['customer']))
        ->assertOk()
        ->assertJsonPath('data.coupon.valid', false)
        ->assertJsonPath('data.coupon.reason', 'already_redeemed')
        ->assertJsonPath('data.discount', 0)
        ->assertJsonPath('data.total', 2900);

    // Anonymous quotes cannot know who is asking.
    $this->postJson('/api/quotes', $quote)->assertOk()->assertJsonPath('data.coupon.valid', true);
});

it('lets another customer use a coupon with a per-customer limit', function (): void {
    Coupon::factory()->limited(null, perCustomer: 1)->create(['code' => 'ONCE']);
    dispatchCompleted(completedCheckout(['couponCode' => 'ONCE', 'customerId' => 'SOMEONE-ELSE']));

    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE', 'quantity' => 1]], 'couponCode' => 'ONCE'], bearer(['customer']))
        ->assertOk()
        ->assertJsonPath('data.coupon.valid', true);
});

it('rejects an exhausted coupon, with or without a token', function (): void {
    Coupon::factory()->limited(2)->create(['code' => 'FIRST2']);
    $quote = ['lines' => [['sku' => 'TEE', 'quantity' => 1]], 'couponCode' => 'FIRST2'];

    dispatchCompleted(completedCheckout(['couponCode' => 'FIRST2', 'checkoutId' => 'CHECKOUT-1']));
    $this->postJson('/api/quotes', $quote)->assertOk()->assertJsonPath('data.coupon.valid', true);

    dispatchCompleted(completedCheckout(['couponCode' => 'FIRST2', 'checkoutId' => 'CHECKOUT-2']));
    $this->postJson('/api/quotes', $quote)->assertOk()->assertJsonPath('data.coupon.reason', 'exhausted');
    $this->postJson('/api/quotes', $quote, bearer(['customer']))->assertOk()->assertJsonPath('data.coupon.reason', 'exhausted');
});

it('reports the per-customer limit before the total one', function (): void {
    Coupon::factory()->limited(1, perCustomer: 1)->create(['code' => 'ONLY1']);
    dispatchCompleted(completedCheckout(['couponCode' => 'ONLY1', 'customerId' => '01JBCDEFGHJKMNPQRSTVWXYZ99']));

    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE', 'quantity' => 1]], 'couponCode' => 'ONLY1'], bearer(['customer']))
        ->assertOk()
        ->assertJsonPath('data.coupon.reason', 'already_redeemed');
});

it('still counts redemptions past a limit: concurrent checkouts may both pass the quote', function (): void {
    $coupon = Coupon::factory()->limited(1)->create(['code' => 'ONLY1']);

    dispatchCompleted(completedCheckout(['couponCode' => 'ONLY1', 'checkoutId' => 'CHECKOUT-1']));
    dispatchCompleted(completedCheckout(['couponCode' => 'ONLY1', 'checkoutId' => 'CHECKOUT-2']));

    expect($coupon->refresh()->redemption_count)->toBe(2);
});

it('401s a quote with an invalid token', function (): void {
    $this->postJson('/api/quotes', ['lines' => []], ['Authorization' => 'Bearer nonsense'])->assertUnauthorized();
});

it('lists a coupon\'s redemptions newest first, paginated', function (): void {
    $coupon = Coupon::factory()->inactive()->create(['code' => 'WELCOME10']);
    foreach (['01', '02', '03'] as $minute) {
        dispatchCompleted(completedCheckout(['checkoutId' => "CHECKOUT-{$minute}", 'completedAt' => "2026-10-26T12:{$minute}:00Z"]));
    }

    CouponRedemption::query()->create([
        'checkout_id' => 'OTHER', 'coupon_id' => Coupon::factory()->create()->id, 'order_id' => 'O', 'customer_id' => 'C',
        'discount' => 1, 'currency' => 'USD', 'redeemed_at' => now(),
    ]);

    $this->getJson('/api/admin/coupons/welcome10/redemptions?perPage=2', bearer())
        ->assertOk()
        ->assertExactJson([
            'data' => [
                ['checkoutId' => 'CHECKOUT-03', 'orderId' => '01JBCDEFGHJKMNPQRSTVWXYZ44', 'customerId' => '01JBCDEFGHJKMNPQRSTVWXYZ11', 'discount' => 580, 'currency' => 'USD', 'redeemedAt' => '2026-10-26T12:03:00Z'],
                ['checkoutId' => 'CHECKOUT-02', 'orderId' => '01JBCDEFGHJKMNPQRSTVWXYZ44', 'customerId' => '01JBCDEFGHJKMNPQRSTVWXYZ11', 'discount' => 580, 'currency' => 'USD', 'redeemedAt' => '2026-10-26T12:02:00Z'],
            ],
            'meta' => ['page' => 1, 'perPage' => 2, 'total' => 3, 'lastPage' => 2],
        ]);

    $this->getJson('/api/admin/coupons/WELCOME10/redemptions?perPage=2&page=2', bearer())
        ->assertOk()
        ->assertJsonPath('data.0.checkoutId', 'CHECKOUT-01');

    expect($coupon->refresh()->redemption_count)->toBe(3);
});

it('guards the redemptions list', function (): void {
    Coupon::factory()->create(['code' => 'WELCOME10']);

    $this->getJson('/api/admin/coupons/NOPE/redemptions', bearer())->assertNotFound();
    $this->getJson('/api/admin/coupons/WELCOME10/redemptions?perPage=101', bearer())->assertUnprocessable();
    $this->getJson('/api/admin/coupons/WELCOME10/redemptions')->assertUnauthorized();
    $this->getJson('/api/admin/coupons/WELCOME10/redemptions', bearer(['customer']))->assertForbidden();
});
