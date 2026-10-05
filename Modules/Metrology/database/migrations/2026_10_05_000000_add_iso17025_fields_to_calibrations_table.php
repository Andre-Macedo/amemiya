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
            $table->string('as_received_condition')->nullable()->after('notes');
            $table->date('received_date')->nullable()->after('as_received_condition');
            $table->boolean('show_calibration_due')->default(true)->after('received_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->dropColumn(['as_received_condition', 'received_date', 'show_calibration_due']);
        });
    }
};
