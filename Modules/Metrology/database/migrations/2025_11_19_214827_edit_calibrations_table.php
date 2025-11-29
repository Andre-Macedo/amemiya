<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {

        Schema::table('calibrations', function (Blueprint $table) {
            if (Schema::hasColumn('calibrations', 'calibration_interval')) {
                $table->dropColumn('calibration_interval');
            }

            $table->decimal('temperature', 4, 1)->nullable()->comment('°C');
            $table->decimal('humidity', 4, 1)->nullable()->comment('%');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->integer('calibration_interval')->nullable();
            $table->dropColumn(['temperature', 'humidity']);
        });
    }
};
