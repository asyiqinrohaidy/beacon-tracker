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
        Schema::create('fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('spot_name'); // e.g. "Spot A", "Near Window"
            $table->string('location_name'); // e.g. "Workshop First Floor"
            $table->integer('gateway_1_rssi'); // Workshop First Floor gateway
            $table->integer('gateway_2_rssi'); // Meeting Room Second Floor gateway
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fingerprints');
    }
};
