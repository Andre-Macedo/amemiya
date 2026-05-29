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
        Schema::create('machines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Uma máquina pertence a uma bancada/posto de trabalho (Station)
            $table->foreignUlid('station_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('code')->nullable(); // Código interno da máquina (ex: TOR-01)
            $table->text('description')->nullable();

            // Status operacional: active, maintenance, inactive
            $table->string('status')->default('active');

            $table->json('metadata')->nullable(); // Para especificações técnicas flexíveis

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
