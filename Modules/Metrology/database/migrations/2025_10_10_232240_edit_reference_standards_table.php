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
            if(Schema::hasColumn('reference_standards', 'type')) {
                $table->dropColumn('type');
            }
            if(!Schema::hasColumn('reference_standards', 'reference_standard_type_id')) {
                $table->foreignId('reference_standard_type_id')->after('serial_number')
                    ->nullable()
                    ->references('id')
                    ->on('reference_standard_types');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->dropForeign(['reference_standard_type_id']);
            $table->dropColumn('reference_standard_type_id');
            $table->string('type');
        });

    }
};
