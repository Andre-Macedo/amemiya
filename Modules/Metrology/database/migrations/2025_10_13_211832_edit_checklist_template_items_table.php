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
            $table->dropColumn('reference_standard_type');
            $table->foreignId('reference_standard_type_id')->nullable()->after('required_readings')->constrained('reference_standard_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_template_items', function (Blueprint $table) {
            $table->dropForeign(['reference_standard_type_id']);
            $table->dropColumn('reference_standard_type_id');
            $table->string('reference_standard_type')->nullable();
        });
    }
};
