<?php

declare(strict_types=1);

use App\Models\Price;
use App\Models\Sku;
use Shopwise\Contracts\Events\Pricing\PriceChangedV1;
use Shopwise\Kit\Messaging\OutboxMessage;

it('sets a price and records pricing.price-changed keyed by sku', function (): void {
    Sku::factory()->create(['sku' => 'TEE-MER-BLA-M']);

    $this->putJson('/api/admin/prices/TEE-MER-BLA-M', ['amount' => 2900, 'currency' => 'USD'], bearer())
        ->assertOk()
        ->assertExactJson(['data' => ['sku' => 'TEE-MER-BLA-M', 'amount' => 2900, 'currency' => 'USD']]);

    $price = Price::query()->findOrFail('TEE-MER-BLA-M');
    $events = recordedEvents(PriceChangedV1::TYPE);
    expect($events)->toEqual([[
        'sku' => 'TEE-MER-BLA-M',
        'amount' => 2900,
        'currency' => 'USD',
        'changedAt' => $price->updated_at->toIso8601ZuluString(),
    ]])
        ->and(array_keys($events[0]))->toEqualCanonicalizing(array_keys(contractExample('pricing.price-changed.v1')->data))
        ->and(OutboxMessage::query()->sole()->subject)->toBe('TEE-MER-BLA-M');
});

it('records a new event only when the amount changes', function (): void {
    Price::factory()->forSku('TEE', 2900)->create();

    $this->putJson('/api/admin/prices/TEE', ['amount' => 2900, 'currency' => 'USD'], bearer())->assertOk();
    expect(recordedEvents(PriceChangedV1::TYPE))->toBe([]);

    $this->putJson('/api/admin/prices/TEE', ['amount' => 3100, 'currency' => 'USD'], bearer())
        ->assertOk()
        ->assertJsonPath('data.amount', 3100);
    expect(recordedEvents(PriceChangedV1::TYPE))->toHaveCount(1)
        ->and(recordedEvents(PriceChangedV1::TYPE)[0]['amount'])->toBe(3100);
});

it('returns 404 for a SKU catalog never announced', function (): void {
    $this->putJson('/api/admin/prices/UNKNOWN', ['amount' => 100, 'currency' => 'USD'], bearer())->assertNotFound();

    expect(Price::query()->count())->toBe(0);
});

it('validates the amount and only accepts USD', function (): void {
    Sku::factory()->create(['sku' => 'TEE']);

    $this->putJson('/api/admin/prices/TEE', ['amount' => -1, 'currency' => 'EUR'], bearer())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'currency']);
    $this->putJson('/api/admin/prices/TEE', ['amount' => 9.99, 'currency' => 'USD'], bearer())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
});

it('needs an admin token', function (): void {
    Sku::factory()->create(['sku' => 'TEE']);

    $this->putJson('/api/admin/prices/TEE', ['amount' => 100, 'currency' => 'USD'])->assertUnauthorized();
    $this->putJson('/api/admin/prices/TEE', ['amount' => 100, 'currency' => 'USD'], bearer(['customer']))->assertForbidden();
});
