<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            // Null = unlimited.
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('max_redemptions_per_customer')->nullable();
            // Rows in coupon_redemptions, kept here so quotes and the admin list need no count query.
            $table->unsignedInteger('redemption_count')->default(0);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table): void {
            // One redemption per checkout: a redelivered or re-published checkout.completed is a no-op.
            $table->string('checkout_id', 26)->primary();
            $table->foreignUlid('coupon_id')->constrained();
            $table->string('order_id', 26);
            // JWT `sub` of the customer.
            $table->string('customer_id', 64);
            $table->unsignedBigInteger('discount');
            $table->char('currency', 3);
            $table->timestamp('redeemed_at');

            $table->index(['coupon_id', 'customer_id']);
            $table->index(['coupon_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');

        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropColumn(['max_redemptions', 'max_redemptions_per_customer', 'redemption_count']);
        });
    }
};
