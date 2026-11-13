<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_gateways', function (Blueprint $table) {
            if (! Schema::hasColumn('iot_gateways', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('status');
            }
        });

        Schema::table('iot_nodes', function (Blueprint $table) {
            if (! Schema::hasColumn('iot_nodes', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('battery_voltage');
            }
        });
    }
};
