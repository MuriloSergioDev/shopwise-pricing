<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // Stored uppercase; deactivated coupons keep their code, so a code is never reused.
            $table->string('code', 32)->unique();
            $table->string('description', 200)->nullable();
            $table->string('type', 10);
            // Percent (1-100) or minor units, depending on type.
            $table->unsignedInteger('value');
            $table->unsignedBigInteger('min_subtotal')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
