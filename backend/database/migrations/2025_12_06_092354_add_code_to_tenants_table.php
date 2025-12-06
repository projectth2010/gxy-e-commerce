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
        if (!Schema::hasColumn('tenants', 'code')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('code')->unique()->after('id');
            });
            
            // Set default code for existing tenants
            \DB::table('tenants')->update([
                'code' => \Illuminate\Support\Str::random(8)
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
