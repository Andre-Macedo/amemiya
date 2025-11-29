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

        Schema::table('instruments', function (Blueprint $table) {
            $table->renameColumn('precision', 'uncertainty');

            $table->string('manufacturer')->nullable()->after('name');
            $table->string('measuring_range')->nullable()->after('uncertainty');
            $table->string('resolution')->nullable()->after('measuring_range');
        });

        Schema::table('instrument_types', function (Blueprint $table) {
            $table->integer('calibration_frequency_months')->default(12)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::table('instruments', function (Blueprint $table) {
            $table->renameColumn('uncertainty', 'precision');
            $table->dropColumn(['manufacturer', 'measuring_range', 'resolution']);
        });

        Schema::table('instrument_types', function (Blueprint $table) {
            $table->dropColumn(['calibration_frequency_months']);
        });

    }
};
