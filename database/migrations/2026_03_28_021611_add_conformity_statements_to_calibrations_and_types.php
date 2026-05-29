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
        Schema::table('instrument_types', function (Blueprint $table) {
            $table->text('pass_statement_template')->nullable()->after('guard_band_multiplier');
            $table->text('fail_statement_template')->nullable()->after('pass_statement_template');
        });

        Schema::table('calibrations', function (Blueprint $table) {
            $table->text('conformity_statement')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument_types', function (Blueprint $table) {
            $table->dropColumn(['pass_statement_template', 'fail_statement_template']);
        });

        Schema::table('calibrations', function (Blueprint $table) {
            $table->dropColumn('conformity_statement');
        });
    }
};
