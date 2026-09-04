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
            $table->string('cloud_ml_status')->nullable()->after('ml_confidence');
            $table->decimal('cloud_ml_confidence', 10, 4)->nullable()->after('cloud_ml_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iot_sensor_data', function (Blueprint $table) {
            $table->dropColumn(['cloud_ml_status', 'cloud_ml_confidence']);
        });
    }
};
