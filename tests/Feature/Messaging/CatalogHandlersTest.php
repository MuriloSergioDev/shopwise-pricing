<?php

declare(strict_types=1);

use App\Models\Sku;
use Shopwise\Kit\Messaging\DispatchResult;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageDispatcher;

it('records the SKU of a changed variant exactly once when redelivered', function (): void {
    $envelope = contractExample('catalog.variant-changed.v1');
    $dispatcher = app(MessageDispatcher::class);

    expect($dispatcher->dispatch($envelope, 'catalog.variant-changed'))->toBe(DispatchResult::Handled)
        ->and($dispatcher->dispatch($envelope, 'catalog.variant-changed'))->toBe(DispatchResult::Duplicate);

    $sku = Sku::query()->sole();
    expect($sku->sku)->toBe('TEE-MER-BLK-M')
        ->and($sku->active)->toBeTrue()
        ->and($sku->changed_at->toIso8601ZuluString())->toBe('2026-10-26T12:00:00Z');
});

it('marks a deactivated variant inactive and makes the SKU priceable either way', function (): void {
    $dispatcher = app(MessageDispatcher::class);
    $dispatcher->dispatch(contractExample('catalog.variant-changed.v1'), 'catalog.variant-changed');

    $deactivated = contractExample('catalog.variant-changed.v1', ['active' => false, 'changedAt' => '2026-10-26T13:00:00Z']);
    // A new message (new id), so the dedup table does not swallow it.
    $deactivated = Envelope::fromArray([...$deactivated->toArray(), 'id' => '01JBCDEFGHJKMNPQRSTVWXYZ26']);
    $dispatcher->dispatch($deactivated, 'catalog.variant-changed');

    expect(Sku::query()->findOrFail('TEE-MER-BLK-M')->active)->toBeFalse();
    $this->putJson('/api/admin/prices/TEE-MER-BLK-M', ['amount' => 2900, 'currency' => 'USD'], bearer())->assertOk();
});

it('ignores a variant state older than the one it has', function (): void {
    Sku::factory()->inactive()->create(['sku' => 'TEE-MER-BLK-M', 'changed_at' => '2026-10-27T00:00:00Z']);

    app(MessageDispatcher::class)->dispatch(contractExample('catalog.variant-changed.v1'), 'catalog.variant-changed');

    expect(Sku::query()->findOrFail('TEE-MER-BLK-M')->active)->toBeFalse();
});

it('records every variant of a published product', function (): void {
    $envelope = contractExample('catalog.product-published.v1');
    $dispatcher = app(MessageDispatcher::class);

    expect($dispatcher->dispatch($envelope, 'catalog.product-published'))->toBe(DispatchResult::Handled)
        ->and($dispatcher->dispatch($envelope, 'catalog.product-published'))->toBe(DispatchResult::Duplicate)
        ->and(Sku::query()->orderBy('sku')->pluck('active', 'sku')->all())->toBe([
            'TEE-MER-BLK-L' => true,
            'TEE-MER-BLK-M' => true,
        ]);
});
