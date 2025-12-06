<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plan_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('feature_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable();
            $table->timestamps();
            
            $table->unique(['plan_id', 'feature_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_feature');
    }
};
