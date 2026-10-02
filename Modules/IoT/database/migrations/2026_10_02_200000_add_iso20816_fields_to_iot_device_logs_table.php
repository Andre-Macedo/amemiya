<?php

declare(strict_types=1);

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
        Schema::table('iot_device_logs', function (Blueprint $table) {
            $table->decimal('velocity_rms', 8, 4)->nullable()->after('rms_global'); // mm/s
            $table->string('iso_zone', 5)->nullable()->after('velocity_rms'); // A, B, C, D
            $table->json('iso_evaluation')->nullable()->after('iso_zone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iot_device_logs', function (Blueprint $table) {
            $table->dropColumn(['velocity_rms', 'iso_zone', 'iso_evaluation']);
        });
    }
};
