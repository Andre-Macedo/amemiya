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
        Schema::table('intermediate_checks', function (Blueprint $table) {
            if (! Schema::hasColumn('intermediate_checks', 'nominal_value')) {
                $table->decimal('nominal_value', 12, 5)->nullable()->after('result');
            }
            if (! Schema::hasColumn('intermediate_checks', 'measured_value')) {
                $table->decimal('measured_value', 12, 5)->nullable()->after('nominal_value');
            }
            if (! Schema::hasColumn('intermediate_checks', 'deviation')) {
                $table->decimal('deviation', 12, 5)->nullable()->after('measured_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intermediate_checks', function (Blueprint $table) {
            $table->dropColumn(['nominal_value', 'measured_value', 'deviation']);
        });
    }
};
