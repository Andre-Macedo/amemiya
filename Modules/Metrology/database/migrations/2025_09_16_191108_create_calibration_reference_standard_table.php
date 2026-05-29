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
        Schema::create('calibration_reference_standard', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('calibration_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('reference_standard_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_reference_standard');
    }
};
