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
        Schema::table('calibrations', function (Blueprint $table) {
            $table->string('as_found_result')->nullable()->after('result');
            $table->string('as_left_result')->nullable()->after('as_found_result');
            $table->decimal('as_found_deviation', 10, 5)->nullable()->after('deviation');
            $table->decimal('as_left_deviation', 10, 5)->nullable()->after('as_found_deviation');

            // Refatoração sutil: renomear 'result' para ser o resultado final/geral da OS
            // e usar esses novos campos para detalhamento da medição antes e depois de ajustes.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->dropColumn([
                'as_found_result',
                'as_left_result',
                'as_found_deviation',
                'as_left_deviation',
            ]);
        });
    }
};
