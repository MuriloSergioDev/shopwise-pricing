<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CreateCoupon;
use App\Actions\RecordSku;
use App\Actions\SetPrice;
use App\Data\NewCouponData;
use App\Enums\CouponType;
use App\Models\Coupon;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Prices for every SKU of catalog's demo seeder, plus two coupons. The SKUs are also written to the read model so
 * pricing works without Kafka; they get an old timestamp so the real catalog events, when consumed, win.
 * Goes through the actions, so the cluster also sees pricing.price-changed. Running it again does nothing.
 */
final class DatabaseSeeder extends Seeder
{
    /**
     * SKU prefix => [unit price in cents, SKU suffixes]. Mirrors shopwise-catalog's DatabaseSeeder
     * (prefix + first three letters of each option, uppercased).
     *
     * @var array<string, array{int, list<string>}>
     */
    private const array PRODUCTS = [
        'TEE-MER' => [2900, ['BLA-S', 'BLA-M', 'BLA-L', 'NAV-S', 'NAV-M', 'NAV-L']],
        'SHI-LIN' => [5900, ['WHI-M', 'WHI-L', 'SAG-M', 'SAG-L']],
        'HOO-HVY' => [6900, ['GRE-S', 'GRE-M', 'GRE-L', 'GRE-XL', 'BLA-S', 'BLA-M', 'BLA-L', 'BLA-XL']],
        'JEA-SEL' => [12900, ['IND-30', 'IND-32', 'IND-34']],
        'SHO-CHI' => [3900, ['KHA-30', 'KHA-32', 'NAV-30', 'NAV-32']],
        'SKL-CST' => [4500, ['']],
        'COF-POUR' => [3800, ['WHI', 'CHA']],
    ];

    public function run(RecordSku $recordSku, SetPrice $setPrice, CreateCoupon $createCoupon): void
    {
        if (Coupon::query()->exists()) {
            return;
        }

        $seededAt = CarbonImmutable::parse('2000-01-01T00:00:00Z');
        foreach (self::PRODUCTS as $prefix => [$amount, $suffixes]) {
            foreach ($suffixes as $suffix) {
                $sku = $suffix === '' ? $prefix : "{$prefix}-{$suffix}";
                $recordSku->handle($sku, true, $seededAt);
                $setPrice->handle($sku, Money::ofMinor($amount, 'USD'));
            }
        }

        $createCoupon->handle(new NewCouponData(code: 'WELCOME10', type: CouponType::Percent, value: 10, description: '10% off your order'));
        $createCoupon->handle(new NewCouponData(code: 'SAVE5', type: CouponType::Fixed, value: 500, description: '$5 off orders of $25 or more', minSubtotal: 2500));
    }
}
