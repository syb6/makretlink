<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customer_profiles')->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')->cascadeOnDelete();
            $table->boolean('notify_on_restock')->default(true);
            $table->timestamps();

            $table->unique(['customer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_products');
    }
};
