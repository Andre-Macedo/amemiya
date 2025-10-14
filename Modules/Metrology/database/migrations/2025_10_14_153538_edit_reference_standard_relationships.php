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
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->foreignId('reference_standard_id')
                ->nullable()
                ->after('completed');
        });

        Schema::dropIfExists('calibration_reference_standard');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('calibration_reference_standard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calibration_id');
            $table->foreignId('reference_standard_id');
            $table->timestamps();
        });

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropForeign(['reference_standard_id']);
            $table->dropColumn('reference_standard_id');
        });

    }
};
