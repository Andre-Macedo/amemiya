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
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->string('asset_tag')
                ->nullable()
                ->unique()
                ->after('serial_number')
                ->comment('Código de Patrimônio');

            $columnsToRemove = ['calibration_date', 'calibration_due', 'certificate_path', 'traceability'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('reference_standards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->date('calibration_date')->nullable();
            $table->date('calibration_due')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('traceability')->nullable();

            $table->dropColumn('asset_tag');
        });
    }
};
