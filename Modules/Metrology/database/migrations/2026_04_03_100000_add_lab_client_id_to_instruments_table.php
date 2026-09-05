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
        Schema::table('instruments', function (Blueprint $table) {
            $table->foreignUlid('lab_client_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('lab_clients')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_client_id');
        });
    }
};
