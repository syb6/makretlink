<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()
                ->constrained('users')->cascadeOnDelete();
            $table->string('business_name', 150);
            $table->string('contact_person', 120);
            $table->text('address');
            $table->text('description')->nullable();
            $table->string('profile_image')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_profiles');
    }
};
