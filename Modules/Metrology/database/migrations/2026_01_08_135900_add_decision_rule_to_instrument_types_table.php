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
        Schema::table('instrument_types', function (Blueprint $table) {
            $table->string('decision_rule')->default('simple')->after('calibration_frequency_months')
                ->comment('simple, uncertainty_accounted, guard_band');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument_types', function (Blueprint $table) {
            $table->dropColumn('decision_rule');
        });
    }
};
