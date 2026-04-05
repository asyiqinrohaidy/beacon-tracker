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
        Schema::table('fingerprints', function (Blueprint $table) {
            $table->integer('gateway_3_rssi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fingerprints', function (Blueprint $table) {
            $table->dropColumn('gateway_3_rssi');
        });
    }
};
