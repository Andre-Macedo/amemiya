<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name'); // e.g., "Steel", "Ceramic"
            $table->decimal('cte', 8, 2); // Coefficient of Thermal Expansion (x 10^-6 / K)
            $table->string('category')->nullable(); // e.g., "Metal", "Ceramic"
            $table->timestamps();
        });

        // Add material_id to instruments
        Schema::table('instruments', function (Blueprint $table) {
            $table->foreignUlid('material_id')->nullable()->constrained('materials')->nullOnDelete();
        });

        // Add material_id to reference_standards
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->foreignUlid('material_id')->nullable()->constrained('materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->dropForeign(['material_id']);
            $table->dropColumn('material_id');
        });

        Schema::table('instruments', function (Blueprint $table) {
            $table->dropForeign(['material_id']);
            $table->dropColumn('material_id');
        });

        Schema::dropIfExists('materials');
    }
};
