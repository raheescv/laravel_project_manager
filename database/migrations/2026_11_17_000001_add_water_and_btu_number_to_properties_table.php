<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'water_number')) {
                $table->string('water_number')->nullable()->after('gas_meter_number');
            }
            if (! Schema::hasColumn('properties', 'btu_number')) {
                $table->string('btu_number')->nullable()->after('water_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            foreach (['btu_number', 'water_number'] as $column) {
                if (Schema::hasColumn('properties', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
