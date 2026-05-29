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
        Schema::create('iot_nodes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Qual gateway (ESP Gateway) este nó de borda usa
            $table->foreignUlid('gateway_id')->constrained('iot_gateways')->cascadeOnDelete();

            // Em qual Máquina este nó está fixado
            $table->foreignUlid('machine_id')->constrained('machines')->cascadeOnDelete();

            $table->string('name');
            $table->string('node_id')->unique(); // ID único do nó de borda (ex: node_vib_01)
            $table->string('status')->default('active'); // active, inactive

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_nodes');
    }
};
