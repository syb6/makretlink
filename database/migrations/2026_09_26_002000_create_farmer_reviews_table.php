<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_reviews', function (Blueprint $table) {
            $table->id();

            
            $table->foreignId('customer_id')
                ->constrained('customer_profiles');
            $table->foreignId('farmer_id')
                ->constrained('farmer_profiles');
            $table->foreignId('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('farmer_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->enum('status', ['visible', 'hidden'])->default('visible')->index();
            $table->timestamps();

            $table->unique(['customer_id', 'farmer_id', 'order_id'], 'farmer_review_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_reviews');
    }
};
