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

            $table->morphs('calibrated_item'); // Cria `calibrated_item_id` e `calibrated_item_type`

            if (Schema::hasColumn('calibrations', 'instrument_id')) {
                try {
                    $table->dropForeign(['instrument_id']);
                } catch (\Exception $e) { /* Ignora se a FK não existir */ }
                $table->dropColumn('instrument_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->foreignId('instrument_id')->nullable()->constrained();

            $table->dropMorphs('calibrated_item');
        });
    }
};
