<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Add notification-specific fields
            $table->string('action_url', 500)->nullable()->after('related_post_id');
            $table->text('preview_text')->nullable()->after('action_url');
            $table->boolean('is_email_sent')->default(false)->after('is_read');
            
            // Add composite index for common queries
            $table->index(['user_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['action_url', 'preview_text', 'is_email_sent']);
        });
    }
};
