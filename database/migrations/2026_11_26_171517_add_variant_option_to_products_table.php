<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a variant family calls its first layer ("Design", "Flavour", "Model"…),
 * the one picked before size and colour. NULL is "Design". The value itself
 * lives in `variant_value`.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'variant_option')) {
                $table->string('variant_option', 40)->nullable()->after('size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'variant_option')) {
                $table->dropColumn('variant_option');
            }
        });
    }
};
