<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')
                ->constrained('farmer_profiles')->cascadeOnDelete();
            $table->foreignId('market_id')
                ->constrained('markets')->cascadeOnDelete();
            $table->string('stall_name', 150)->nullable();
            $table->string('stall_location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->unique(['farmer_id', 'market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_markets');
    }
};
