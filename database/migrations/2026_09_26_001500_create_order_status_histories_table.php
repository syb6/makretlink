<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->enum('status', [
                'placed',
                'accepted',
                'declined',
                'ready_for_pickup',
                'completed',
                'cancelled'
            ]);
            $table->foreignId('changed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
