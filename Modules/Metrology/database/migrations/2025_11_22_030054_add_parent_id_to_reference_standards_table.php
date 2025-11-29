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
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('reference_standards')
                ->cascadeOnDelete();

            $table->boolean('is_kit')->default(false)->after('stock_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->dropForeign('reference_standards_parent_id');
            $table->dropColumn('parent_id');
            $table->dropColumn('is_kit');
        });
    }
};
