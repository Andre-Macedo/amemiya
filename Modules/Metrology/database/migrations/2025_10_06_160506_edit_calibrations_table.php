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
            $table->foreignId('checklist_id')->nullable()->after('instrument_id');
            $table->integer('calibration_interval')->nullable()->after('type')->after('checklist_id')
                ->comment('Intervalo de calibracao, em meses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->dropForeign('calibrations_checklist_id_foreign');
            $table->dropColumn('calibration_interval');
        });
    }
};
