<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('customer_id')->nullable()
                ->constrained('customer_profiles')->nullOnDelete();

            $table->foreignId('farmer_market_id')->nullable()
                ->constrained('farmer_markets')->nullOnDelete();
            $table->foreignId('pickup_slot_id')->nullable()
                ->constrained('pickup_slots')->nullOnDelete();

            $table->string('customer_name', 120);
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('farmer_name', 150);
            $table->string('market_name', 150);
            $table->date('pickup_date');
            $table->time('pickup_start_time');
            $table->time('pickup_end_time');

            $table->enum('status', [
                'placed',
                'accepted',
                'declined',
                'ready_for_pickup',
                'completed',
                'cancelled'
            ])->default('placed')->index();

            $table->decimal('total_amount', 12, 2);
            $table->text('customer_note')->nullable();
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
