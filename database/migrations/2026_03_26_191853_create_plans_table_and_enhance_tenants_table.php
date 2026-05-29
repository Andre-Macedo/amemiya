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
        // 1. Criar Tabela de Planos
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);

            // Limites do Plano
            $table->integer('max_instruments')->default(50)->comment('0 para ilimitado');
            $table->integer('max_users')->default(5)->comment('0 para ilimitado');
            $table->integer('max_storage_mb')->default(1024); // 1GB padrão

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Enriquecer Tabela de Tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignUlid('plan_id')->nullable()->after('id')->constrained('plans')->nullOnDelete();

            // Status e Lifecycle
            $table->string('status')->default('trial')->after('slug'); // trial, active, suspended, canceled
            $table->timestamp('trial_ends_at')->nullable()->after('status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');

            // Informações de Contato
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('address')->nullable();

            // Overrides de Limites (Permite customizar por cliente sem mudar o plano)
            $table->integer('limit_instruments_override')->nullable();
            $table->integer('limit_users_override')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id', 'status', 'trial_ends_at', 'subscription_ends_at',
                'contact_email', 'contact_phone', 'address',
                'limit_instruments_override', 'limit_users_override',
            ]);
        });

        Schema::dropIfExists('plans');
    }
};
