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
        Schema::table('checklists', function (Blueprint $table) {
            $table->foreignId('checklist_template_id')->after('calibration_id');
            $table->dropColumn('steps');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('checklists', function (Blueprint $table) {
            $table->dropForeign('checklists_checklist_template_id_foreign');
            $table->dropColumn('checklist_template_id');
            $table->dropSoftDeletes();
            $table->integer('steps')->after('calibration_id');
        });
    }
};
