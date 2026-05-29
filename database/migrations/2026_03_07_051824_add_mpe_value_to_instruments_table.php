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
        Schema::table('instruments', function (Blueprint $table) {
            $table->decimal('mpe_value', 12, 6)->nullable()->after('mpe')
                ->comment('Valor numérico do Erro Máximo Permissível para cálculos automáticos.');

            $table->string('model')->nullable()->after('manufacturer')
                ->comment('Modelo do instrumento fornecido pelo fabricante.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropColumn(['mpe_value', 'model']);
        });
    }
};
