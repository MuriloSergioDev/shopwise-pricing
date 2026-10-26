<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Models\Price;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    Price::factory()->forSku('TEE', 2900)->create();
    Price::factory()->forSku('MUG', 1250)->create();
});

it('prices lines and leaves unpriced SKUs out of the subtotal', function (): void {
    $this->postJson('/api/quotes', ['lines' => [
        ['sku' => 'TEE', 'quantity' => 2],
        ['sku' => 'UNPRICED', 'quantity' => 1],
        ['sku' => 'MUG', 'quantity' => 1],
    ]])
        ->assertOk()
        ->assertExactJson(['data' => [
            'currency' => 'USD',
            'lines' => [
                ['sku' => 'TEE', 'quantity' => 2, 'unitAmount' => 2900, 'lineAmount' => 5800],
                ['sku' => 'UNPRICED', 'quantity' => 1, 'unitAmount' => null, 'lineAmount' => null],
                ['sku' => 'MUG', 'quantity' => 1, 'unitAmount' => 1250, 'lineAmount' => 1250],
            ],
            'subtotal' => 7050,
            'discount' => 0,
            'total' => 7050,
            'coupon' => null,
        ]]);
});

it('quotes an empty cart as zero', function (): void {
    $this->postJson('/api/quotes', ['lines' => []])
        ->assertOk()
        ->assertJsonPath('data.lines', [])
        ->assertJsonPath('data.total', 0);
});

it('applies a percent coupon rounded half-up, matching the code case-insensitively', function (): void {
    Coupon::factory()->percent(15)->create(['code' => 'SPRING15', 'description' => 'Spring sale']);

    // 15% of 1250 = 187.5 → 188.
    $this->postJson('/api/quotes', ['lines' => [['sku' => 'MUG', 'quantity' => 1]], 'couponCode' => 'spring15'])
        ->assertOk()
        ->assertJsonPath('data.subtotal', 1250)
        ->assertJsonPath('data.discount', 188)
        ->assertJsonPath('data.total', 1062)
        ->assertJsonPath('data.coupon', ['code' => 'SPRING15', 'valid' => true, 'reason' => null, 'description' => 'Spring sale']);
});

it('never discounts more than the subtotal', function (): void {
    Coupon::factory()->fixed(5000)->create(['code' => 'BIG']);

    $this->postJson('/api/quotes', ['lines' => [['sku' => 'MUG', 'quantity' => 1]], 'couponCode' => 'BIG'])
        ->assertOk()
        ->assertJsonPath('data.discount', 1250)
        ->assertJsonPath('data.total', 0);
});

it('returns an invalid coupon with its reason instead of failing', function (array $coupon, string $reason): void {
    Coupon::factory()->fixed(500)->create(['code' => 'CODE', ...$coupon]);

    $this->postJson('/api/quotes', ['lines' => [['sku' => 'MUG', 'quantity' => 1]], 'couponCode' => 'code'])
        ->assertOk()
        ->assertJsonPath('data.coupon.valid', false)
        ->assertJsonPath('data.coupon.reason', $reason)
        ->assertJsonPath('data.coupon.code', 'CODE')
        ->assertJsonPath('data.discount', 0)
        ->assertJsonPath('data.total', 1250);
})->with([
    'deactivated' => [['active' => false], 'unknown'],
    'not started' => [['starts_at' => now()->addDay()], 'not_started'],
    'expired' => [['ends_at' => now()->subMinute()], 'expired'],
    'subtotal too low' => [['min_subtotal' => 2500], 'min_subtotal_not_met'],
]);

it('reports an unknown code as unknown', function (): void {
    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE', 'quantity' => 1]], 'couponCode' => 'nope'])
        ->assertOk()
        ->assertJsonPath('data.coupon', ['code' => 'NOPE', 'valid' => false, 'reason' => 'unknown', 'description' => null]);
});

it('applies a coupon whose minimum subtotal is reached', function (): void {
    Coupon::factory()->fixed(500)->create(['code' => 'SAVE5', 'min_subtotal' => 2500]);

    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE', 'quantity' => 1]], 'couponCode' => 'SAVE5'])
        ->assertOk()
        ->assertJsonPath('data.coupon.valid', true)
        ->assertJsonPath('data.discount', 500)
        ->assertJsonPath('data.total', 2400);
});

it('stores nothing', function (): void {
    Coupon::factory()->create(['code' => 'X']);
    $before = DB::table('outbox_messages')->count() + DB::table('coupons')->count() + DB::table('prices')->count();

    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE', 'quantity' => 1]], 'couponCode' => 'X'])->assertOk();

    expect(DB::table('outbox_messages')->count() + DB::table('coupons')->count() + DB::table('prices')->count())->toBe($before);
});

it('validates lines', function (): void {
    $this->postJson('/api/quotes', [])->assertUnprocessable()->assertJsonValidationErrors('lines');
    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE', 'quantity' => 0]]])
        ->assertUnprocessable()->assertJsonValidationErrors('lines.0.quantity');
    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE', 'quantity' => 100]]])
        ->assertUnprocessable()->assertJsonValidationErrors('lines.0.quantity');
    $this->postJson('/api/quotes', ['lines' => array_fill(0, 51, ['sku' => 'TEE', 'quantity' => 1])])
        ->assertUnprocessable()->assertJsonValidationErrors('lines');
});
