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
        Schema::table('iot_sensor_data', function (Blueprint $table) {
            if (! Schema::hasColumn('iot_sensor_data', 'temp_celsius')) {
                $table->decimal('temp_celsius', 6, 2)->nullable()->after('mic_rms');
            }
        });

        Schema::table('iot_nodes', function (Blueprint $table) {
            if (! Schema::hasColumn('iot_nodes', 'battery_voltage')) {
                $table->decimal('battery_voltage', 4, 2)->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iot_sensor_data', function (Blueprint $table) {
            if (Schema::hasColumn('iot_sensor_data', 'temp_celsius')) {
                $table->dropColumn('temp_celsius');
            }
        });

        Schema::table('iot_nodes', function (Blueprint $table) {
            if (Schema::hasColumn('iot_nodes', 'battery_voltage')) {
                $table->dropColumn('battery_voltage');
            }
        });
    }
};
