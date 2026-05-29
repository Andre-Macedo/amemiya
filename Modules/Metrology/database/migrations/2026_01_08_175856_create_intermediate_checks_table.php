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
        Schema::create('intermediate_checks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('instrument_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('reference_standard_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->date('check_date');
            $table->string('result'); // pass, fail
            $table->foreignUlid('performed_by')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intermediate_checks');
    }
};
