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
        Schema::table('checklist_templates', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('name');
            $table->boolean('is_active')->default(true)->after('version');

            $table->foreignUlid('parent_version_id')
                ->nullable()
                ->after('is_active')
                ->constrained('checklist_templates')
                ->nullOnDelete();

            $table->text('revision_notes')->nullable()->after('parent_version_id');
            $table->timestamp('published_at')->nullable()->after('revision_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_templates', function (Blueprint $table) {
            $table->dropForeign(['parent_version_id']);
            $table->dropColumn(['version', 'is_active', 'parent_version_id', 'revision_notes', 'published_at']);
        });
    }
};
