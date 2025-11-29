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
        Schema::table('reference_standards', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->change();
            $table->string('stock_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Reverter é arriscado se tiver dados nulos, mas segue o padrão:
//        Schema::table('reference_standards', function (Blueprint $table) {
//            $table->string('serial_number')->nullable(false)->change();
//
//        });

    }
};
