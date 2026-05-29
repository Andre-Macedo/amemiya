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
        Schema::create('lab_clients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('name');
            $table->string('cnpj')->nullable()->index();
            $table->string('email')->nullable();

            // Credenciais de acesso ao Portal White-label
            $table->string('access_token')->unique()->comment('Código de acesso único do cliente ao portal');
            $table->string('password')->nullable(); // Opcional, se quiser login tradicional

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // CNPJ único por laboratório
            $table->unique(['tenant_id', 'cnpj']);
        });

        // Vincular calibrações ao cliente final (opcional, mas bom para filtrar)
        Schema::table('calibrations', function (Blueprint $table) {
            $table->foreignUlid('lab_client_id')->nullable()->after('tenant_id')->constrained('lab_clients')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->dropForeign(['lab_client_id']);
            $table->dropColumn('lab_client_id');
        });
        Schema::dropIfExists('lab_clients');
    }
};
