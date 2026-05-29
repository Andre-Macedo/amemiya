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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained();

            $table->string('name')->default('default'); // Identificador da assinatura (ex: 'main')

            // Gateway info
            $table->string('gateway'); // asaas, mercadopago, stripe, manual
            $table->string('gateway_id')->nullable()->index(); // ID da assinatura no provedor externo
            $table->string('gateway_status')->nullable(); // Status original retornado pelo gateway

            // Local State (Normalizado)
            $table->string('status'); // trialing, active, past_due, canceled, ended

            // Datas de ciclo
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->json('metadata')->nullable(); // Para guardar IDs extras ou logs do gateway

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
