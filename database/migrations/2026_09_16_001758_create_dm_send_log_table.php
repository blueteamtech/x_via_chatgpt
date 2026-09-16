<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_send_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('content_hash', 64);
            $table->string('recipient_id');
            $table->timestamp('sent_at');

            $table->index(['user_id', 'sent_at']);
            $table->index(['user_id', 'content_hash', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_send_log');
    }
};
