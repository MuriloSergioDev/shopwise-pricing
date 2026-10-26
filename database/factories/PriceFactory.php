<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Price;
use App\Models\Sku;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
final class PriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => fn (): string => Sku::factory()->create()->sku,
            'price' => Money::ofMinor(fake()->numberBetween(100, 20000), 'USD'),
        ];
    }

    /**
     * A price for this SKU (created in the read model when missing).
     */
    public function forSku(string $sku, int $amount): self
    {
        return $this->state(fn (): array => [
            'sku' => Sku::query()->find($sku)->sku ?? Sku::factory()->create(['sku' => $sku])->sku,
            'price' => Money::ofMinor($amount, 'USD'),
        ]);
    }
}
