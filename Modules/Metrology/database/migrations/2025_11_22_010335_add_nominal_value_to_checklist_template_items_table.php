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
            $table->decimal('nominal_value', 10, 4)->nullable()->after('question_type')
                ->comment('O valor alvo do teste (ex: 25.00)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_template_items', function (Blueprint $table) {
            $table->dropColumn('nominal_value');
        });
    }
};
