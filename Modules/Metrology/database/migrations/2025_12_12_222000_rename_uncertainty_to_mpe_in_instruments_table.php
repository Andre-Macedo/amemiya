<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('instruments', function (Blueprint $table) {
            // Renomeia para o termo correto (Erro Máximo Permissível)
            if (Schema::hasColumn('instruments', 'uncertainty')) {
                $table->renameColumn('uncertainty', 'mpe');
            } elseif (! Schema::hasColumn('instruments', 'mpe')) {
                $table->decimal('mpe', 10, 5)->nullable();
            }
        });

        // Remove 'mm', espaços e substitui vírgula por ponto se houver
        DB::statement("UPDATE instruments SET mpe = REPLACE(REPLACE(mpe, 'mm', ''), ' ', '')");

        // Se houver algum valor vazio string, define como null para não dar erro
        DB::statement("UPDATE instruments SET mpe = NULL WHERE mpe = ''");

        Schema::table('instruments', function (Blueprint $table) {
            // Adiciona comentários e campos técnicos
            $table->decimal('mpe', 10, 5)
                ->comment('Maximum Permissible Error (Erro Máximo Permissível / Tolerância)')
                ->change();

            // Adicionamos resolução se não tiver, pois é vital para o cálculo da incerteza
            if (Schema::hasColumn('instruments', 'resolution') && Schema::hasColumn('instruments', 'measuring_range')) {
                $table->string('resolution')->nullable()->comment('Menor divisão (ex: 0.01)')->change();
                $table->string('measuring_range')->nullable()->comment('Capacidade (ex: 0-150mm)')->change();
            }
        });

        Schema::table('calibrations', function (Blueprint $table) {
            $table->decimal('k_factor', 4, 2)->default(2.00)->comment('Fator de Abrangência');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('instruments', function (Blueprint $table) {
            $table->renameColumn('mpe', 'uncertainty');
        });
    }
};
