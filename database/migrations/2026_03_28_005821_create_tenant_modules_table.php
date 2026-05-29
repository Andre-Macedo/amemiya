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
        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('module_id'); // ID do módulo no config/amemiya.php

            $table->timestamp('activated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable(); // Para add-ons com validade

            $table->json('settings')->nullable(); // Configurações específicas do módulo para este cliente

            $table->timestamps();

            // Garantir que um tenant não ative o mesmo módulo duas vezes
            $table->unique(['tenant_id', 'module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};
