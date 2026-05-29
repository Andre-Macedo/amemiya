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
        Schema::table('stations', function (Blueprint $table) {
            $table->foreignUlid('parent_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('stations')
                ->nullOnDelete();

            // Alteramos o comentário da coluna 'type' existente para refletir a nova hierarquia
            $table->string('type')->default('workstation')->change()
                ->comment('plant, department, sector, workstation, storage');

            $table->text('description')->nullable()->after('location');
            $table->boolean('is_active')->default(true)->after('description');
            $table->json('metadata')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'description', 'is_active', 'metadata']);
        });
    }
};
