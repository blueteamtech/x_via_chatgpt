<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null tier means grandfathered / beta / owner — bypasses credit caps.
            // Real tiers: 'publisher' | 'pro'
            $table->string('subscription_tier')->nullable()->after('email');
            $table->string('subscription_status')->nullable()->after('subscription_tier');
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_status');
            $table->unsignedInteger('credits_used_this_month')->default(0)->after('subscription_started_at');
            $table->timestamp('credits_reset_at')->nullable()->after('credits_used_this_month');
            $table->string('stripe_customer_id')->nullable()->after('credits_reset_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_tier',
                'subscription_status',
                'subscription_started_at',
                'credits_used_this_month',
                'credits_reset_at',
                'stripe_customer_id',
            ]);
        });
    }
};
