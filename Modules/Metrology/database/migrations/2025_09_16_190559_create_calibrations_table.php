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
        Schema::create('calibrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('instrument_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('calibration_date');
            $table->string('type')->default('internal');
            $table->string('result')->nullable();
            $table->decimal('deviation', 8, 4)->nullable();
            $table->decimal('uncertainty', 8, 4)->nullable();
            $table->text('notes')->nullable();
            $table->string('certificate_path')->nullable();
            $table->foreignUlid('performed_by_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibrations');
    }
};
