<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            if (! Schema::hasColumn('instruments', 'criticality')) {
                $table->string('criticality')->default('operational_reference')->after('status')->index()
                    ->comment('Classificação de criticidade (safety_nr12, safety_nr13, product_quality_ctq, environmental, operational_reference)');
            }
        });
    }
};
