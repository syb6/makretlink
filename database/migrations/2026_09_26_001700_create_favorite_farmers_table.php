<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_farmers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customer_profiles')->cascadeOnDelete();
            $table->foreignId('farmer_id')
                ->constrained('farmer_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'farmer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_farmers');
    }
};
