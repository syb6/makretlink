<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')->cascadeOnDelete();
            $table->foreignId('farmer_market_id')
                ->constrained('farmer_markets')->cascadeOnDelete();

            $table->foreignId('stock_template_id')->nullable()
                ->constrained('stock_templates')->nullOnDelete();

            $table->date('week_start')->index();
            $table->decimal('quantity', 10, 2);
            $table->decimal('available_quantity', 10, 2);
            $table->enum('status', ['available', 'sold_out', 'unavailable'])->default('available')->index();
            $table->timestamps();

            $table->unique(['product_id', 'farmer_market_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_stocks');
    }
};
