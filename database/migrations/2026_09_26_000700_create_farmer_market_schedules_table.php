<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_market_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_market_id')
                ->constrained('farmer_markets')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday ... 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['farmer_market_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_market_schedules');
    }
};
