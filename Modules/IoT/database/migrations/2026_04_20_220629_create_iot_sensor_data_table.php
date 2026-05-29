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
        Schema::create('iot_sensor_data', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Qual nó enviou esse dado
            $table->foreignUlid('node_id')->constrained('iot_nodes')->cascadeOnDelete();

            // Metadados da Mensagem
            $table->unsignedBigInteger('msg_id')->nullable();

            // Features Globais
            $table->integer('rpm')->nullable();
            $table->decimal('rms_global', 10, 4)->nullable();

            // Features de Domínio do Tempo (Vibração)
            $table->decimal('rms_x', 10, 4)->nullable();
            $table->decimal('rms_y', 10, 4)->nullable();
            $table->decimal('rms_z', 10, 4)->nullable();
            $table->decimal('kurt_x', 10, 4)->nullable();
            $table->decimal('kurt_y', 10, 4)->nullable();
            $table->decimal('kurt_z', 10, 4)->nullable();

            // Features Piezoelétricas (Acústica/Alta Frequência)
            $table->decimal('piezo_rms', 10, 4)->nullable();
            $table->decimal('piezo_pico_max', 10, 4)->nullable();
            $table->decimal('piezo_fator_crista', 10, 4)->nullable();

            // Dados de Frequência (FFT - Mantido em JSON pois arrays variam de tamanho)
            $table->json('fft_data')->nullable();

            // Resultados do Machine Learning
            $table->string('ml_status')->nullable(); // Ex: saudavel, desbalanceamento, falha_rolamento
            $table->decimal('ml_confidence', 5, 4)->nullable();

            // Timestamp original do sensor (se disponível) ou do sistema
            $table->timestamp('measured_at');

            $table->timestamps();

            // Indexar para performance de gráficos históricos e análises temporais
            $table->index(['node_id', 'measured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_sensor_data');
    }
};
