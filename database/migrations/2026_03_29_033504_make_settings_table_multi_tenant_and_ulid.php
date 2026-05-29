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
        // Como o banco é maleável, vamos recriar a tabela settings com a nova estrutura
        Schema::dropIfExists('settings');

        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('key');
            $table->text('value')->nullable();

            $table->timestamps();

            // Uma chave de configuração deve ser única por cliente
            $table->unique(['tenant_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');

        // Reverte para o estado original BigInt se necessário
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }
};
