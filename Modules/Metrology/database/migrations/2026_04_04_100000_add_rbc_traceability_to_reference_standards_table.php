<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reference_standards', function (Blueprint $table) {
            if (! Schema::hasColumn('reference_standards', 'certificate_number')) {
                $table->string('certificate_number')->nullable()->after('uncertainty')
                    ->comment('Número do certificado de calibração do padrão (ex: CAL-1234/2026)');
            }

            if (! Schema::hasColumn('reference_standards', 'accredited_lab')) {
                $table->string('accredited_lab')->nullable()->after('certificate_number')
                    ->comment('Laboratório emissor / Órgão acreditador (ex: Mitutoyo - RBC CAL 0031)');
            }

            if (! Schema::hasColumn('reference_standards', 'traceability_chain')) {
                $table->string('traceability_chain')->nullable()->after('accredited_lab')
                    ->comment('Cadeia de rastreabilidade (ex: Rastreado ao Inmetro / Cgcre RBC / BIPM)');
            }
        });
    }
};
