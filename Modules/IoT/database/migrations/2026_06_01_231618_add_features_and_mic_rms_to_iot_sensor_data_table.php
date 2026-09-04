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
        Schema::table('iot_sensor_data', function (Blueprint $table) {
            // Renomeia piezo_rms para mic_rms se existir, ou cria mic_rms
            if (Schema::hasColumn('iot_sensor_data', 'piezo_rms')) {
                $table->renameColumn('piezo_rms', 'mic_rms');
            } else {
                $table->decimal('mic_rms', 10, 4)->nullable()->after('kurt_z');
            }

            // Adiciona a coluna features
            $table->json('features')->nullable()->after('fft_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iot_sensor_data', function (Blueprint $table) {
            if (Schema::hasColumn('iot_sensor_data', 'mic_rms')) {
                $table->renameColumn('mic_rms', 'piezo_rms');
            }
            $table->dropColumn('features');
        });
    }
};
