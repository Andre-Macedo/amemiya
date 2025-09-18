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
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serial_number')->unique();
            $table->string('type'); // ex: paquimetro, micrometro
            $table->string('precision')->nullable(); // centesimal, milesimal
            $table->string('location')->nullable();
            $table->date('acquisition_date');
            $table->date('calibration_due');
            $table->string('status')->default('active'); // active, in_calibration, expired

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('location');
            $table->index('calibration_due');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
