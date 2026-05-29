<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_conformities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Vínculos Polimórficos (Instrumento ou Padrão)
            $table->nullableUlidMorphs('item'); // item_type, item_id

            $table->foreignUlid('calibration_id')->nullable()->constrained('calibrations')->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users');

            // Estado
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');

            // Detalhes
            $table->string('title');
            $table->text('description');

            // Investigação
            $table->text('root_cause_analysis')->nullable();

            // Solução
            $table->text('immediate_action')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();

            // Fechamento
            $table->foreignUlid('closed_by')->nullable()->constrained('users');
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
