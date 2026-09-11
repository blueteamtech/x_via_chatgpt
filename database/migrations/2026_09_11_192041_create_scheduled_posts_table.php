<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->string('reply_to_id')->nullable();
            $table->string('quote_id')->nullable();
            $table->json('media_ids')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'published_at', 'failed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
