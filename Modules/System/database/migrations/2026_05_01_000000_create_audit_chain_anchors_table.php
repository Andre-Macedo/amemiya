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
        Schema::create('audit_chain_anchors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedInteger('sequence_number');
            $table->string('chain_head_hash', 64)->index();
            $table->unsignedInteger('total_records');
            $table->string('anchor_type', 32)->default('WORM_IMMUTABLE');
            $table->string('storage_path', 255)->nullable();
            $table->string('manifest_hash', 64)->index();
            $table->json('manifest_payload');
            $table->timestamp('anchored_at');
            $table->timestamps();

            $table->index(['tenant_id', 'sequence_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_chain_anchors');
    }
};
