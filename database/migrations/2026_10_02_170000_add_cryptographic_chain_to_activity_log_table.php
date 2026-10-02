<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('activitylog.table_name', 'activity_log');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('sequence_number')->nullable()->after('tenant_id');
            $table->char('previous_hash', 64)->nullable()->after('sequence_number');
            $table->char('record_hash', 64)->nullable()->after('previous_hash');

            $table->index(['tenant_id', 'sequence_number']);
            $table->index('record_hash');
        });
    }

    public function down(): void
    {
        $tableName = config('activitylog.table_name', 'activity_log');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'sequence_number']);
            $table->dropIndex(['record_hash']);
            $table->dropColumn(['sequence_number', 'previous_hash', 'record_hash']);
        });
    }
};
