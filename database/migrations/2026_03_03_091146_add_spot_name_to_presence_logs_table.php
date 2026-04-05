<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presence_logs', function (Blueprint $table) {
            $table->string('spot_name')->nullable()->after('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('presence_logs', function (Blueprint $table) {
            $table->dropColumn('spot_name');
        });
    }
};
