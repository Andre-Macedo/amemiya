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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Razão Social
            $table->string('trade_name')->nullable();
            $table->string('cnpj')->unique()->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Flags de Capacidade (Padrão de Indústria)
            $table->boolean('is_manufacturer')->default(false);
            $table->boolean('is_calibration_provider')->default(false); // Lab RBC
            $table->boolean('is_maintenance_provider')->default(false); // Faz manutenção?

            // Controle de Qualidade (ISO 17025)
            $table->string('rbc_code')->nullable()->comment('Código da Acreditação');
            $table->date('accreditation_valid_until')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
