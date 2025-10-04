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
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->text('step');
            $table->string('question_type')->default('boolean'); // boolean, numeric, text
            $table->integer('order');
            $table->boolean('completed')->default(false);
            $table->integer('required_readings')->default(0);
            $table->json('readings')->nullable(); // Array of measurements, e.g., [25.001, 25.002]
            $table->decimal('uncertainty', 10, 4)->nullable();
            $table->string('result')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
