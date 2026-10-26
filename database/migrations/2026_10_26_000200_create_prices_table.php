<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table): void {
            $table->string('sku', 64)->primary();
            $table->foreign('sku')->references('sku')->on('skus');
            // Minor units + ISO 4217 code, mapped to Brick\Money\Money by the kit's MoneyCast.
            $table->unsignedBigInteger('price_amount');
            $table->char('price_currency', 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
