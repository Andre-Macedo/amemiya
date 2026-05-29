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
            $table->decimal('guard_band_multiplier', 5, 2)->default(1.0)->after('decision_rule');
        });

        Schema::table('instruments', function (Blueprint $table) {
            $table->decimal('guard_band_multiplier_override', 5, 2)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument_types', function (Blueprint $table) {
            $table->dropColumn('guard_band_multiplier');
        });

        Schema::table('instruments', function (Blueprint $table) {
            $table->dropColumn('guard_band_multiplier_override');
        });
    }
};
