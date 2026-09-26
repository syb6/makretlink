<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')
                ->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')->cascadeOnDelete();
            $table->foreignId('farmer_market_id')
                ->constrained('farmer_markets')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'farmer_market_id'], 'cart_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
