<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reference_standards', function (Blueprint $table) {
            if (Schema::hasColumn('reference_standards', 'asset_tag')) {
                $table->renameColumn('asset_tag', 'stock_number');
            }
        });

        Schema::table('reference_standard_types', function (Blueprint $table) {
            $table->integer('calibration_frequency_months')->default(24)->after('name');
        });

        Schema::table('reference_standards', function (Blueprint $table) {
            $table->date('calibration_due')->nullable()->after('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_standards', function (Blueprint $table) {
            if (Schema::hasColumn('reference_standards', 'stock_number')) {
                $table->renameColumn('stock_number', 'asset_tag');
            }
        });

        Schema::table('reference_standard_types', function (Blueprint $table) {
            $table->dropColumn('calibration_frequency_months');
        });

        Schema::table('reference_standards', function (Blueprint $table) {
            $table->dropColumn('calibration_due');
        });
    }
};
