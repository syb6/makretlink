<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customer_profiles')->cascadeOnDelete();
            $table->foreignId('market_id')
                ->constrained('markets')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_markets');
    }
};
