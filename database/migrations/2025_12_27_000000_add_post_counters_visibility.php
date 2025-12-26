<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add counter fields for performance
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('quotes_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            
            // Add visibility field
            $table->enum('visibility', ['public', 'private', 'followers_only'])->default('public');
            
            // Add original post reference for reposts/quotes
            $table->uuid('original_post_uuid')->nullable();
            
            // Add full-text index for search (MySQL 8.0+)
            $table->text('content')->change();
        });
        
        // Add full-text index for content search
        Schema::table('posts', function (Blueprint $table) {
            $table->fullText(['content'])->parser('ngram');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropFullText(['content']);
            $table->dropColumn([
                'likes_count',
                'reposts_count',
                'quotes_count',
                'replies_count',
                'views_count',
                'visibility',
                'original_post_uuid',
            ]);
        });
    }
};
