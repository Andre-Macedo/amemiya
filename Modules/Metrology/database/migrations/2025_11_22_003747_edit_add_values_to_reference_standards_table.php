<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('reference_standards', function (Blueprint $table) {
            // Dados Fixos (Definidos na compra)
            $table->decimal('nominal_value', 12, 6)->nullable()->after('name')
                ->comment('Valor de face (ex: 50.00)');
            $table->string('unit')->default('mm')->after('nominal_value');

            // Dados Variáveis (Atualizados a cada calibração DELE)
            $table->decimal('actual_value', 12, 6)->nullable()->after('unit')
                ->comment('Valor verdadeiro do último certificado');
            $table->decimal('uncertainty', 12, 6)->nullable()->after('actual_value')
                ->comment('Incerteza herdada do padrão');

            // Bônus: Classe de Exatidão (Grade 0, Grade 1, etc)
            $table->string('grade')->nullable()->after('reference_standard_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->dropColumn('nominal_value');
            $table->dropColumn('unit');
            $table->dropColumn('actual_value');
            $table->dropColumn('uncertainty');
            $table->dropColumn('grade');
        });
    }
};
