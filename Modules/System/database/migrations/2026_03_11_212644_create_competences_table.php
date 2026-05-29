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
        Schema::create('competences', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUlid('instrument_type_id')->constrained('instrument_types')->onDelete('cascade');
            $table->date('valid_until')->nullable(); // Se null, não expira
            $table->timestamps();

            // Um usuário só tem um registro de competência por tipo de instrumento
            $table->unique(['user_id', 'instrument_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competences');
    }
};
