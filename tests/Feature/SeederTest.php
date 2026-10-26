<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Models\Price;
use App\Models\Sku;
use Database\Seeders\DatabaseSeeder;

it('seeds prices for the demo catalog and two coupons once, through the actions', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Sku::query()->count())->toBe(28)
        ->and(Price::query()->count())->toBe(28)
        ->and(recordedEvents('pricing.price-changed'))->toHaveCount(28)
        ->and(Coupon::query()->orderBy('code')->pluck('code')->all())->toBe(['SAVE5', 'WELCOME10']);

    $this->getJson('/api/prices?skus[]=TEE-MER-BLA-M&skus[]=SKL-CST&skus[]=COF-POUR-WHI')
        ->assertOk()
        ->assertJsonPath('data.*.amount', [3800, 4500, 2900]);

    $this->postJson('/api/quotes', ['lines' => [['sku' => 'TEE-MER-BLA-M', 'quantity' => 1]], 'couponCode' => 'save5'])
        ->assertOk()
        ->assertJsonPath('data.total', 2400);
});
