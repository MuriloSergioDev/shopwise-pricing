<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Read model of catalog's SKUs (catalog.variant-changed / catalog.product-published). Prices can only be set
        // for SKUs listed here.
        Schema::create('skus', function (Blueprint $table): void {
            $table->string('sku', 64)->primary();
            $table->boolean('active');
            // Time of the catalog event that last changed the row: older events arriving late are ignored.
            $table->timestamp('changed_at', 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skus');
    }
};
