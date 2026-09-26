<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_market_id')
                ->constrained('farmer_markets')->cascadeOnDelete();
            $table->date('pickup_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->dateTime('cutoff_at')->index();
            $table->unsignedInteger('max_orders')->nullable();
            $table->enum('status', ['active', 'inactive', 'full'])->default('active')->index();
            $table->timestamps();

            $table->unique(['farmer_market_id', 'pickup_date', 'start_time', 'end_time'], 'pickup_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_slots');
    }
};
