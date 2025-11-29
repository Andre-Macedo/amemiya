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
            $table->string('status')
                ->default('active')
                ->after('stock_number')
                ->index()
                ->comment('active, in_calibration, expired, rejected, lost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
