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
        Schema::table('calibrations', function (Blueprint $table) {
            $table->foreignUlid('replaces_calibration_id')->nullable()->after('id')->constrained('calibrations');
            $table->text('amendment_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->dropForeign(['replaces_calibration_id']);
            $table->dropColumn(['replaces_calibration_id', 'amendment_reason']);
        });
    }
};
