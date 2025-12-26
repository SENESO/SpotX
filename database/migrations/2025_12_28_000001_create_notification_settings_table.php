<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade);
            
            // Push/Email notifications preferences
            $table->boolean('notify_likes')->default(true);
            $table->boolean('notify_reposts')->default(true);
            $table->boolean('notify_quotes')->default(true);
            $table->boolean('notify_replies')->default(true);
            $table->boolean('notify_mentions')->default(true);
            $table->boolean('notify_follows')->default(true);
            $table->boolean('notify_follower_posts')->default(false);
            
            // Quiet hours
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->default('22:00:00');
            $table->time('quiet_hours_end')->default('08:00:00');
            
            // Email digest preferences
            $table->boolean('email_digest_enabled')->default(true);
            $table->enum('email_digest_frequency', ['never', 'daily', 'weekly'])->default('daily');
            
            $table->timestamps();

            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
