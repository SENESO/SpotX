<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('media_url', 500);
            $table->string('thumbnail_url', 500)->nullable();
            $table->enum('media_type', ['image', 'video']);
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable()->comment('Duration in seconds for video');
            $table->timestamps();
            
            $table->index('post_id');
            $table->index('media_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
