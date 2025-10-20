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
        Schema::table('instruments', function (Blueprint $table) {
            $table->string('nfc_tag')->nullable()->unique();
            $table->foreignId('current_station_id')->nullable()->constrained('stations');
            $table->string('status_stock')->default('available'); // available, in_use
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropForeign(['current_station_id']);
            $table->dropColumn(['nfc_tag', 'current_station_id', 'status_stock']);
        });
    }
};
