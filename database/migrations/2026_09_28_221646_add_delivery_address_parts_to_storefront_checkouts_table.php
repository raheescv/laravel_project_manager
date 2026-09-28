<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery addresses are collected as Qatar's blue-plate parts — zone, street,
 * building — plus the city, so a driver can find the door. `address` stays as
 * the one-line rendering the sale carries. The pin comes from QNAS when the
 * customer picked the building from its list; typed-in addresses have none.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_checkouts', function (Blueprint $table) {
            $table->string('zone_number', 10)->nullable()->after('customer_email');
            $table->string('street_number', 10)->nullable()->after('zone_number');
            $table->string('building_number', 10)->nullable()->after('street_number');
            $table->string('city', 100)->nullable()->after('building_number');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_checkouts', function (Blueprint $table) {
            $table->dropColumn(['zone_number', 'street_number', 'building_number', 'city', 'latitude', 'longitude']);
        });
    }
};
