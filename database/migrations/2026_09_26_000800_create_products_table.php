<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')
                ->constrained('farmer_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()
                ->constrained('categories')->nullOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('unit', 30);
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['farmer_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
