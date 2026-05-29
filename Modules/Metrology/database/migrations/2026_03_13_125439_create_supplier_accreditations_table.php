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
        Schema::create('supplier_accreditations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignUlid('instrument_type_id')->constrained('instrument_types')->onDelete('cascade');
            $table->string('range')->nullable()->comment('Ex: 0 to 150mm');
            $table->string('uncertainty')->nullable()->comment('Best uncertainty CMC');
            $table->timestamps();

            $table->unique(['tenant_id', 'supplier_id', 'instrument_type_id'], 'supplier_type_accreditation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_accreditations');
    }
};
