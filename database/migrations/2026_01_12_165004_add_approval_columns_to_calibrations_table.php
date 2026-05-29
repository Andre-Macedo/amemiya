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
            $table->foreignUlid('approved_by_id')->nullable()->constrained('users')->after('performed_by_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            $table->string('status')->default('draft')->after('type')->comment('draft, submitted, published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table) {
            $table->dropForeign(['approved_by_id']);
            $table->dropColumn(['approved_by_id', 'approved_at', 'status']);
        });
    }
};
