<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customer_profiles');
            $table->foreignId('product_id')
                ->constrained('products');
            $table->foreignId('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->enum('status', ['visible', 'hidden'])->default('visible')->index();
            $table->timestamps();

            $table->unique(['customer_id', 'product_id', 'order_id'], 'product_review_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
