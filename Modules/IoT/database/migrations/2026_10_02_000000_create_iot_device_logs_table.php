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
        Schema::create('iot_device_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignUlid('gateway_id')->nullable()->constrained('iot_gateways')->nullOnDelete();
            $table->foreignUlid('node_id')->nullable()->constrained('iot_nodes')->nullOnDelete();
            $table->foreignUlid('machine_id')->nullable()->constrained('machines')->nullOnDelete();

            // Severidade e Tipo de Evento
            $table->string('level')->default('info'); // info, warning, error, critical
            $table->string('event_type'); // anomaly_detected, cloud_ml_evaluated, command_dispatched, telemetry_received, error

            // Vereditos e Confiança
            $table->string('ml_status')->nullable();
            $table->decimal('ml_confidence', 5, 4)->nullable();
            $table->string('cloud_ml_status')->nullable();
            $table->decimal('cloud_ml_confidence', 5, 4)->nullable();

            // Métricas Rápidas
            $table->integer('rpm')->nullable();
            $table->decimal('rms_global', 10, 4)->nullable();

            // Cargas Completas para Auditoria / Diagnóstico
            $table->json('raw_payload')->nullable();
            $table->json('features')->nullable();
            $table->json('sent_command')->nullable();

            $table->string('message')->nullable();
            $table->timestamp('measured_at')->nullable();
            $table->timestamps();

            // Índices para busca ágil
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'node_id', 'created_at']);
            $table->index(['tenant_id', 'event_type', 'created_at']);
            $table->index(['tenant_id', 'level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_device_logs');
    }
};
