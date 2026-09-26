<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')
                ->constrained('markets')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday ... 6=Saturday
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['market_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_schedules');
    }
};
