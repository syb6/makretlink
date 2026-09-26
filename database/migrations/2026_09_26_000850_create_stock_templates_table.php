<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')->cascadeOnDelete();
            $table->foreignId('farmer_market_id')
                ->constrained('farmer_markets')->cascadeOnDelete();
            $table->decimal('default_quantity', 10, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['product_id', 'farmer_market_id'], 'stock_template_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_templates');
    }
};
