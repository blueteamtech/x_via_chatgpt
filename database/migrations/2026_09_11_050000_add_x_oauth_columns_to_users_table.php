<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('x_id')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->index()->after('name');
            $table->text('x_access_token')->nullable();
            $table->text('x_refresh_token')->nullable();
            $table->timestamp('x_token_expires_at')->nullable();
            $table->json('x_token_scopes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'x_id',
                'username',
                'x_access_token',
                'x_refresh_token',
                'x_token_expires_at',
                'x_token_scopes',
            ]);
        });
    }
};
