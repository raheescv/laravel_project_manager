<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first layer of a variant family: its design / flavour / model (named by
 * the family's `variant_option`). Sizes and colours sit under it, so one item
 * is e.g. Floral → M → Navy; a drink may carry a flavour and no size at all.
 * The family index now covers all three.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'variant_value')) {
                $table->string('variant_value', 100)->nullable()->after('variant_option');
                $table->index(['parent_id', 'variant_value'], 'products_parent_variant_value_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'variant_value')) {
                $table->dropIndex('products_parent_variant_value_index');
                $table->dropColumn('variant_value');
            }
        });
    }
};
