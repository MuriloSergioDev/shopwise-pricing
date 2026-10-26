<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
final class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper(fake()->unique()->bothify('CODE-####')),
            'description' => fake()->sentence(4),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_subtotal' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'active' => true,
        ];
    }

    public function percent(int $percent): self
    {
        return $this->state(['type' => CouponType::Percent, 'value' => $percent]);
    }

    public function fixed(int $amount): self
    {
        return $this->state(['type' => CouponType::Fixed, 'value' => $amount]);
    }

    public function limited(?int $total, ?int $perCustomer = null): self
    {
        return $this->state(['max_redemptions' => $total, 'max_redemptions_per_customer' => $perCustomer]);
    }

    public function inactive(): self
    {
        return $this->state(['active' => false]);
    }
}
