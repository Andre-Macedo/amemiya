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
            $table->dropColumn('type');
            $table->foreignId('instrument_type_id')->after('serial_number')->nullable()->constrained();
            $table->string('stock_number')->after('name')->nullable();
            $table->string('image_path')->after('current_station_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('instruments', function (Blueprint $table) {
            $table->string('type');
            $table->dropForeign('instrument_type_id');
            $table->dropColumn('instrument_type_id');
            $table->dropColumn('stock_number');
            $table->dropColumn('image_path');
        });
    }
};
