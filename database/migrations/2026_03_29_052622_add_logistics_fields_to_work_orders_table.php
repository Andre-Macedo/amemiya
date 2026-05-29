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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignUlid('origin_station_id')->nullable()->after('received_by_id')->constrained('stations')->nullOnDelete();
            $table->foreignUlid('destination_station_id')->nullable()->after('origin_station_id')->constrained('stations')->nullOnDelete();

            $table->string('courier_name')->nullable()->after('destination_station_id')->comment('Nome do transportador/responsável físico');
            $table->timestamp('dispatched_at')->nullable()->after('courier_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['origin_station_id']);
            $table->dropForeign(['destination_station_id']);
            $table->dropColumn(['origin_station_id', 'destination_station_id', 'courier_name', 'dispatched_at']);
        });
    }
};
