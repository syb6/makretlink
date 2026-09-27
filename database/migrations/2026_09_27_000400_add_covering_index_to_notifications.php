<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The navbar counts unread notifications on every authenticated page
     * view. A covering index (notifiable, read_at) lets MySQL answer it
     * from the index alone instead of scanning the user's rows.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_unread_covering_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_unread_covering_index');
        });
    }
};
