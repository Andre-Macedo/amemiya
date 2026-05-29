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
        Schema::table('checklist_template_items', function (Blueprint $table) {
            $table->decimal('criteria', 12, 6)->nullable()->after('nominal_value')
                ->comment('Critério de aceitação (tolerância +/-) para este ponto específico.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_template_items', function (Blueprint $table) {
            $table->dropColumn('criteria');
        });
    }
};
