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
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained(); // Quem enviou a mensagem

            $table->text('message');
            $table->boolean('is_internal')->default(false); // Só o Super Admin vê

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
