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
        Schema::create('instrument_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('instrument_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // checkin, checkout, transfer, inventory_check
            $table->string('tag_id')->nullable()->index(); // O ID lido do hardware

            $table->foreignUlid('from_station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->foreignUlid('to_station_id')->nullable()->constrained('stations')->nullOnDelete();

            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('metadata')->nullable()->comment('Infos do leitor, força do sinal RSSI, etc');

            $table->timestamps();
        });

        // Garantir que a nfc_tag no instrumento seja indexada para buscas rápidas
        Schema::table('instruments', function (Blueprint $table) {
            $table->index('nfc_tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropIndex(['nfc_tag']);
        });
        Schema::dropIfExists('instrument_movements');
    }
};
