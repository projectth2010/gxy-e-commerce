<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Indexes for subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            // For active subscriptions lookup
            $table->index(['stripe_status', 'trial_ends_at'], 'idx_active_subscriptions');
            
            // For churn rate calculations
            $table->index(['ends_at', 'stripe_status'], 'idx_churn_calculation');
            
            // For trial ending soon queries
            $table->index(['trial_ends_at', 'stripe_status'], 'idx_trial_ending');
            
            // For renewal queries
            $table->index(['current_period_end', 'stripe_status'], 'idx_renewals');
        });

        // Indexes for payments table
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // For failed payments and renewal success rate
                $table->index(['status', 'type', 'created_at'], 'idx_payment_status_type_date');
                
                // For payment failure analysis
                $table->index(['status', 'created_at'], 'idx_payment_failures');
            });
        }

        // Indexes for payment methods
        if (Schema::hasTable('payment_methods')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                // For expiring cards
                $table->index(['exp_month', 'exp_year'], 'idx_expiring_cards');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_active_subscriptions');
            $table->dropIndex('idx_churn_calculation');
            $table->dropIndex('idx_trial_ending');
            $table->dropIndex('idx_renewals');
        });

        // Drop indexes from payments table if it exists
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payment_status_type_date');
                $table->dropIndex('idx_payment_failures');
            });
        }

        // Drop indexes from payment_methods table if it exists
        if (Schema::hasTable('payment_methods')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropIndex('idx_expiring_cards');
            });
        }
    }
};
