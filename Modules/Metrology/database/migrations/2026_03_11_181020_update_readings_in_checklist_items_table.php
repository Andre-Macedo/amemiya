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
        if (Schema::hasColumn('checklist_items', 'readings')) {
            Schema::table('checklist_items', function (Blueprint $table) {
                $table->renameColumn('readings', 'as_found_readings');
            });
        }

        Schema::table('checklist_items', function (Blueprint $table) {
            if (! Schema::hasColumn('checklist_items', 'as_left_readings')) {
                $table->json('as_left_readings')->nullable()->after('as_found_readings');
            }
            if (! Schema::hasColumn('checklist_items', 'adjusted')) {
                $table->boolean('adjusted')->default(false)->after('as_left_readings');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn(['as_left_readings', 'adjusted']);
            $table->renameColumn('as_found_readings', 'readings');
        });
    }
};
