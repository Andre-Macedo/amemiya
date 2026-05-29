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
        Schema::table('stations', function (Blueprint $table) {
            if (! Schema::hasColumn('stations', 'hostname')) {
                $table->string('hostname')->nullable()->after('location');
            }
            if (! Schema::hasColumn('stations', 'type')) {
                $table->string('type')->default('Workstation')->after('location');
            }
            if (! Schema::hasColumn('stations', 'status')) {
                $table->string('status')->default('Active')->after('location');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            // Suppliers might already have some of these if created by full Filament resource, checking strictly
            if (! Schema::hasColumn('suppliers', 'status')) {
                $table->string('status')->default('Approved')->after('name'); // Approved, Pending, Blocked
            }
            if (! Schema::hasColumn('suppliers', 'category')) {
                $table->string('category')->default('General')->after('status'); // Manufacturer, Calibration Lab, Parts
            }
            if (! Schema::hasColumn('suppliers', 'rating')) {
                $table->integer('rating')->default(5)->after('category'); // 1-5
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['hostname', 'type', 'status']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['status', 'category', 'rating']);
        });
    }
};
