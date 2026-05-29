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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('number')->comment('OS-YYYY-XXXX');
            $table->ulidMorphs('item'); // For both Instruments and Reference Standards
            $table->string('status')->default('received')->comment('received, in_queue, calibrating, finished, dispatched');
            $table->text('visual_inspection_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->foreignUlid('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
