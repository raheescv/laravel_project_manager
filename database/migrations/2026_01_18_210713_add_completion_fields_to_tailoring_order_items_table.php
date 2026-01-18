<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('tailoring_order_items', function (Blueprint $table) {
            // Completion fields are already in the main migration, but this can be used for future additions
            // If needed, add any additional completion fields here
        });
    }

    public function down(): void
    {
        Schema::table('tailoring_order_items', function (Blueprint $table) {
            //
        });
    }
};
