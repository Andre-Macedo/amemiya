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
        Schema::create('iot_gateways', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Opcional: Vincular o gateway físico a uma bancada/área
            $table->foreignUlid('station_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('device_id')->unique(); // O ID que vem no MQTT (ex: gw_wahl_001)
            $table->string('status')->default('online'); // online, offline, maintenance

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_gateways');
    }
};
