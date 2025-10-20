<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained();;
            $table->date('calibration_date');
            $table->string('type')->default('internal');
            $table->string('result')->nullable();
            $table->decimal('deviation', 8, 4)->nullable();
            $table->decimal('uncertainty', 8, 4)->nullable();
            $table->text('notes')->nullable();
            $table->string('certificate_path')->nullable();
            $table->foreignId('performed_by_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibrations');
    }
};
