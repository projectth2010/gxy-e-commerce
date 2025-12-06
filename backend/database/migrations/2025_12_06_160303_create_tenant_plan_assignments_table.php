<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_plan_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            
            // Subscription details
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            
            // Status tracking
            $table->enum('status', ['active', 'pending', 'canceled', 'expired'])->default('pending');
            $table->text('cancellation_reason')->nullable();
            
            // Billing information
            $table->string('billing_cycle');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tenant_id');
            $table->index('plan_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_plan_assignments');
    }
};
