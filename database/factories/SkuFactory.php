<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sku>
 */
final class SkuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => mb_strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'active' => true,
            'changed_at' => now(),
        ];
    }

    public function inactive(): self
    {
        return $this->state(['active' => false]);
    }
}
