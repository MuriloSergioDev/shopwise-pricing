<?php

declare(strict_types=1);

use App\Models\Price;

it('returns the prices of the requested SKUs and leaves out unpriced ones', function (): void {
    Price::factory()->forSku('TEE-MER-BLA-M', 2900)->create();
    Price::factory()->forSku('TEE-MER-BLA-L', 3100)->create();
    Price::factory()->forSku('OTHER', 100)->create();

    $this->getJson('/api/prices?skus[]=TEE-MER-BLA-M&skus[]=TEE-MER-BLA-L&skus[]=NO-PRICE')
        ->assertOk()
        ->assertExactJson(['data' => [
            ['sku' => 'TEE-MER-BLA-L', 'amount' => 3100, 'currency' => 'USD'],
            ['sku' => 'TEE-MER-BLA-M', 'amount' => 2900, 'currency' => 'USD'],
        ]]);
});

it('needs between 1 and 100 SKUs', function (): void {
    $this->getJson('/api/prices')->assertUnprocessable()->assertJsonValidationErrors('skus');

    $tooMany = implode('&', array_map(fn (int $i): string => "skus[]=SKU-{$i}", range(1, 101)));
    $this->getJson("/api/prices?{$tooMany}")->assertUnprocessable()->assertJsonValidationErrors('skus');
});
