<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Property Type No printed in the premises block of the rental lease.
     */
    public function up(): void
    {
        Schema::table('property_buildings', function (Blueprint $table): void {
            if (! Schema::hasColumn('property_buildings', 'property_type_no')) {
                $table->string('property_type_no')->nullable()->after('building_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_buildings', function (Blueprint $table): void {
            if (Schema::hasColumn('property_buildings', 'property_type_no')) {
                $table->dropColumn('property_type_no');
            }
        });
    }
};
