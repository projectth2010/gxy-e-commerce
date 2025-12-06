<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->index()->comment('Stripe Customer ID');
            }
            if (!Schema::hasColumn('tenants', 'pm_type')) {
                $table->string('pm_type')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'pm_last_four')) {
                $table->string('pm_last_four', 4)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
        });

        Schema::table('tenant_plan_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_plan_assignments', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->index();
            }
            if (!Schema::hasColumn('tenant_plan_assignments', 'stripe_status')) {
                $table->string('stripe_status')->nullable();
            }
            if (!Schema::hasColumn('tenant_plan_assignments', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable();
            }
            if (!Schema::hasColumn('tenant_plan_assignments', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
            if (!Schema::hasColumn('tenant_plan_assignments', 'ends_at')) {
                $table->timestamp('ends_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
            ]);
        });

        Schema::table('tenant_plan_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_subscription_id',
                'stripe_status',
                'stripe_price_id',
                'trial_ends_at',
                'ends_at',
            ]);
        });
    }
};
