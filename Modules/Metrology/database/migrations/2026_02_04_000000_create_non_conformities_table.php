<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_conformities', function (Blueprint $table) {
            $table->id();

            // Vínculos
            $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
            $table->foreignId('calibration_id')->nullable()->constrained('calibrations')->nullOnDelete(); // O gatilho (se houver)
            $table->foreignId('user_id')->nullable()->constrained('users'); // Quem abriu (ou sistema)

            // Estado
            $table->string('status')->default('open'); // open, investigating, resolved, closed
            $table->string('priority')->default('medium'); // low, medium, high, critical

            // Detalhes do Problema (O Quê)
            $table->string('title');
            $table->text('description');

            // Investigação (Por Quê - ISO 17025 7.10)
            $table->text('root_cause_analysis')->nullable(); // Análise de Causa Raiz

            // Solução (Como - ISO 17025 8.7)
            $table->text('immediate_action')->nullable(); // Ação Imediata (ex: tirar de uso)
            $table->text('corrective_action')->nullable(); // Ação Corretiva (ex: consertar)
            $table->text('preventive_action')->nullable(); // Ação Preventiva (ex: treinar equipe)

            // Fechamento
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_conformities');
    }
};
