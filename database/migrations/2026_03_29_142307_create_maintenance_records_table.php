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
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('instrument_id')->constrained()->cascadeOnDelete();

            $table->string('type')->default('corrective'); // preventive, corrective, adjustment
            $table->date('date');

            $table->text('description');
            $table->text('findings')->nullable()->comment('O que foi detectado antes da manutenção');

            $table->json('parts_replaced')->nullable();
            $table->decimal('cost', 10, 2)->default(0);

            $table->foreignUlid('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete(); // Se foi feita fora

            $table->string('status')->default('completed'); // pending, in_progress, completed

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
