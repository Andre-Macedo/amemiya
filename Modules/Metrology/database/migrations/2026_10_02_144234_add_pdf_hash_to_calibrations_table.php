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
        Schema::table('calibrations', function (Blueprint $table) {
            if (! Schema::hasColumn('calibrations', 'pdf_hash')) {
                $table->string('pdf_hash', 64)->nullable()->after('certificate_code')->index()->comment('SHA-256 cryptographic hash of final PDF');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table) {
            if (Schema::hasColumn('calibrations', 'pdf_hash')) {
                $table->dropColumn('pdf_hash');
            }
        });
    }
};
