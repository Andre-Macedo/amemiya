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
        Schema::table('checklist_templates', function (Blueprint $table) {
            if(Schema::hasColumn('checklist_templates', 'instrument_type')) {
                $table->dropColumn('instrument_type');
            }
            $table->foreignId('instrument_type_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('checklist_templates', function (Blueprint $table) {
            if(Schema::hasColumn('checklist_templates', 'instrument_type_id')) {
                $table->dropForeign('checklist_templates_instrument_type_id_foreign');
                $table->dropColumn('instrument_type_id');
            }
            $table->string('instrument_type')->nullable();
        });
    }
};
