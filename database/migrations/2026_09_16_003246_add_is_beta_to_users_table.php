<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Beta users bypass the paid-subscription gate. All existing users
            // (owner + any early testers) are grandfathered as beta. New users
            // signing up after this migration will default to false and be
            // required to subscribe.
            $table->boolean('is_beta')->default(false)->after('subscription_status');
        });

        // Backfill: everyone who exists right now stays free.
        DB::table('users')->update(['is_beta' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_beta');
        });
    }
};
